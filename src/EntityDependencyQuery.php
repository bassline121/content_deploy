<?php

namespace Drupal\content_deploy;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The query to get entity ids for the dependency.
 */
class EntityDependencyQuery {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * DependencyQuery constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Executes query and gets entity ids for the dependency.
   *
   * @param string $dep_name
   *   The dependency name.
   * @param callable $callback
   *   The callback to take results that signature is `function
   *   ($id, $entity_type_id, $bundle = NULL, $uuid = NULL)`.
   *
   * @throws \Exception
   *   The entity type of given dependency does not exist.
   */
  public function execute($dep_name, callable $callback) {
    $dependency = Utility::parseContentEntityDependencyName($dep_name);

    $entity_type = $this->entityTypeManager->getDefinition($dependency['entity_type']);

    if (empty($entity_type)) {
      throw new \Exception("Entity type '{$dependency['entity_type']}' is not defined.'");
    }

    $query = $this->entityTypeManager->getStorage($entity_type->id())
      ->getQuery();

    if (!empty($dependency['bundle'])) {
      $bundle_key = $entity_type->getKey('bundle');

      // Skip if the bundle key is empty (when single bundle).
      if (!empty($bundle_key)) {
        $query->condition($bundle_key, $dependency['bundle']);
      }

      if (!empty($dependency['uuid'])) {
        $query->condition($entity_type->getKey('uuid'), $dependency['uuid']);
      }
    }

    $ids = $query->execute();

    foreach ($ids as $id) {
      $callback($id, $dependency['entity_type'], $dependency['bundle'], $dependency['uuid']);
    }
  }

}
