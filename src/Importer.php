<?php

namespace Drupal\content_deploy;

use Drupal\content_deploy\Dump\Blob;
use Drupal\content_deploy\Dump\Dump;
use Drupal\content_deploy\Dump\DumpRestorerFactory;
use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\content_deploy\Exception\MissingDependencyException;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Psr\Log\LoggerInterface;

/**
 * The dump importer.
 */
class Importer implements EntityDependencyResolverInterface {

  const RESULT_CREATED = 'created';

  const RESULT_UPDATED = 'updated';

  /**
   * The key of the source content directory.
   *
   * @var string
   */
  private $source;

  /**
   * The array of an entity dependency name to import.
   *
   * @var string[]
   */
  private $entityDependencyNames;

  /**
   * The array of an dump to import.
   *
   * @var \Drupal\content_deploy\Dump\Dump[]
   */
  private $dumps;

  /**
   * The config for this module.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * The config factory to load config dependencies.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The dump restorer.
   *
   * @var \Drupal\content_deploy\Dump\DumpRestorer
   */
  private $restorer;

  /**
   * The entity repository.
   *
   * @var \Drupal\content_deploy\EntityRepository
   */
  private $entityRepository;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The dump storage.
   *
   * @var \Drupal\content_deploy\Dump\DumpStorage
   */
  private $storage;

  /**
   * The associated array of loaded or imported entities.
   *
   * @var ContentEntityInterface[]
   */
  private $entityCache;

  /**
   * The associated array of imported entities counts.
   *
   * @var int[]
   */
  private $resultCounts = [
    self::RESULT_CREATED => 0,
    self::RESULT_UPDATED => 0,
  ];

  /**
   * Importer constructor.
   */
  public function __construct($source, array $dep_names, Config $config, ConfigFactoryInterface $config_factory, DumpRestorerFactory $restorer_factory, EntityRepository $entity_repository, DumpStorage $storage, LoggerInterface $logger) {
    $this->source = $source;
    $this->entityDependencyNames = $dep_names;
    $this->config = $config;
    $this->configFactory = $config_factory;
    $this->restorer = $restorer_factory->get($this);
    $this->entityRepository = $entity_repository;
    $this->storage = $storage;
    $this->logger = $logger;
  }

  /**
   * Performs the import.
   *
   * @return int[]
   *   The associated array of imported entities counts.
   *   Its keys are RESULT_CREATED and RESULT_UPDATED.
   *   And values are count of each result.
   */
  public function import() {
    $this->resultCounts = [
      static::RESULT_CREATED => 0,
      static::RESULT_UPDATED => 0,
    ];

    $this->dumps = $this->storage->loadMultiple($this->entityDependencyNames);

    $this->ensureAllDependencies();

    foreach ($this->dumps as $dump) {
      $entity = $this->importSingle($dump);
      $this->entityCache[$dump->dependencyName] = $entity;
    }

    return $this->resultCounts;
  }

  /**
   * Ensures all entity dependency for dumps.
   */
  private function ensureAllDependencies() {
    foreach ($this->dumps as $dump) {
      foreach ($dump->getDependencyKeys() as $key) {
        foreach ($dump->getDependenciesForKey($key) as $dep_name) {
          $this->ensureDependency($dep_name);
        }
      }
    }
  }

  /**
   * Ensures the entity dependency.
   */
  public function ensureDependency($dep_name) {
    if (isset($this->entityCache[$dep_name])) {
      return;
    }

    // Import or load an existing entity.
    if (isset($this->dumps[$dep_name])) {
      $dump = $this->dumps[$dep_name];
      $entity = $this->importSingle($dump);
    }
    else {
      $entity = $this->entityRepository->loadEntityByDependencyName($dep_name);
    }

    if (!$entity) {
      throw new MissingDependencyException($dep_name);
    }

    $this->entityCache[$dep_name] = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveEntityDependency($dep_name) {
    if (isset($this->entityCache[$dep_name])) {
      return $this->entityCache[$dep_name];
    }

    throw new MissingDependencyException($dep_name);
  }

  /**
   * Sets the result of current import.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   The imported diff.
   * @param string $result
   *   The import result.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The imported entity.
   */
  private function setImportResult(Dump $dump, $result, ContentEntityInterface $entity) {
    $this->resultCounts[$result]++;
    $this->entityCache[$dump->dependencyName] = $entity;
  }

  /**
   * Imports the dump.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   The imported diff.
   *
   * @return ContentEntityInterface
   *   The imported entity.
   */
  private function importSingle(Dump $dump) {
    if (!empty($dump->blob)) {
      $this->copyBlobFile($dump->dependencyName, $dump->blob);
    }

    $existingEntity = $this->entityRepository->loadEntityByDump($dump);

    if ($existingEntity) {
      $result = static::RESULT_UPDATED;
      $this->overwriteFields($existingEntity, $dump);
    }
    else {
      $result = static::RESULT_CREATED;
      $existingEntity = $this->createEntity($dump);
    }

    $existingEntity->save();

    $this->setImportResult($dump, $result, $existingEntity);

    return $existingEntity;
  }

  /**
   * Copy the blob file to active.
   *
   * @param string $dep_name
   *   The dependency name of the dump.
   * @param \Drupal\content_deploy\Dump\Blob $blob
   *   The blob.
   */
  private function copyBlobFile($dep_name, Blob $blob) {
    $blob_path = $this->storage->getBlobPath($dep_name, $blob);

    if (!file_exists($blob_path)) {
      $this->logger->notice('Blob of @dep_name does not exist', [
        '@dep_name' => $dep_name,
      ]);
    }
    else {
      file_unmanaged_copy($blob_path, $blob->uri, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * Creates new entity from dump.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   A dump.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A created entity.
   */
  private function createEntity(Dump $dump) {
    $fields = $this->restorer->getImportableFields($dump);

    $entity = $this->entityRepository->getStorage($dump->entityTypeId)
      ->create($fields);
    return $entity;
  }

  /**
   * Overwrites fields of entity with dump.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity that fields will be overwritten.
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   A dump.
   */
  private function overwriteFields(ContentEntityInterface $entity, Dump $dump) {
    $fields = $this->restorer->getImportableFields($dump);

    foreach ($fields as $field_name => $value) {
      $entity->set($field_name, $value);
    }
  }

}
