<?php

namespace Drupal\content_deploy;

use Drupal\content_deploy\Dump\Dump;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EntityRepository.
 */
class EntityRepository {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * DiffEntityManager constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Loads the existing entity of the dependency name.
   *
   * @param string $dep_name
   *   The dependency name.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The loaded entity if exists.
   *   Otherwise NULL.
   */
  public function loadEntityByDependencyName($dep_name) {
    $dependency = Utility::parseContentEntityDependencyName($dep_name);

    if ($dependency) {
      $entity = $this->loadEntityByUuid($dependency['entity_type'], $dependency['uuid']);
      return $entity;
    }

    return NULL;
  }

  /**
   * Loads the entity by the dump.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   The dump.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity object, or NULL if there is no entity with the given dump.
   */
  public function loadEntityByDump(Dump $dump) {
    $entity = $this->loadEntityByUuid($dump->entityTypeId, $dump->uuid);
    return $entity;
  }

  /**
   * Loads the entity by UUID.
   *
   * @param string $entity_type_id
   *   The entity type ID to load from.
   * @param string $uuid
   *   The UUID of the entity to load.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity object, or NULL if there is no entity with the given UUID.
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $uuid_key = $entity_type->getKey('uuid');

    $entities = $this->getStorage($entity_type_id)->loadByProperties([
      $uuid_key => $uuid,
    ]);

    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Gets the entity storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Entity storage does not exist, or not valid.
   */
  public function getStorage($entity_type_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    assert($storage instanceof ContentEntityStorageInterface);

    return $storage;
  }

}
