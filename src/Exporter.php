<?php

namespace Drupal\content_deploy;

use Drupal\content_deploy\Dump\Dumper;
use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * The dump exporter.
 */
class Exporter {

  /**
   * The key of the destination content directory.
   *
   * @var string
   */
  private $destination;

  /**
   * The config for the module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The dependency query.
   *
   * @var \Drupal\content_deploy\EntityDependencyQuery
   */
  private $query;

  /**
   * The dump storage.
   *
   * @var \Drupal\content_deploy\Dump\DumpStorage
   */
  private $storage;

  /**
   * The content dumper.
   *
   * @var \Drupal\content_deploy\Dump\Dumper
   */
  private $dumper;

  /**
   * The associated array of exported entities.
   *
   * The key is dependency name of entity.
   *
   * @var array
   */
  private $exportedEntitiesMap;

  /**
   * Exporter constructor.
   */
  public function __construct($destination, Config $config, EntityTypeManagerInterface $entity_type_manager, DumpStorage $storage, EntityDependencyQuery $query, LoggerInterface $logger) {
    $this->destination = $destination;
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->storage = $storage;
    $this->query = $query;
    $this->dumper = new Dumper();
  }

  /**
   * Bulk exports entities to the directory.
   */
  public function export() {
    $this->logger->log(LogLevel::OK, "Bulk export to '{$this->destination}'");

    foreach ($this->config->get('exports') as $dep_name => $settings) {
      $this->bulkExportForDependencyName($dep_name);
    }

    $this->logger->log(LogLevel::SUCCESS, "Complete bulk export to '{$this->destination}'");
  }

  /**
   * Exports one or more entities for the dependency name.
   *
   * @param string $dep_name
   *   The dependency name.
   */
  private function bulkExportForDependencyName($dep_name) {
    $this->logger->log(LogLevel::INFO, "Export $dep_name");

    $this->query->execute($dep_name, function ($id, $entity_type_id, $bundle = NULL, $uuid = NULL) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->load($id);

      $this->exportEntity($entity);
    });
  }

  /**
   * Exports an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity.
   */
  private function exportEntity(ContentEntityInterface $entity) {
    $dump = $this->dumper->dump($entity);

    $dep_name = $dump->dependencyName;
    $this->exportedEntitiesMap[$dep_name] = TRUE;

    $this->storage->save($dump);

    $this->logger->info("Write $dep_name");

    $dependencies = $dump->getDependenciesForKey('content');
    if (!empty($dependencies)) {
      $this->logger->info("Resolve dependencies");

      foreach ($dependencies as $dep_name) {
        if (!isset($this->exportedEntitiesMap[$dep_name])) {
          $this->bulkExportForDependencyName($dep_name);
        }
      }

      $this->logger->info("Completed resolve dependencies");
    }
  }

}
