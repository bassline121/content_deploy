<?php

namespace Drupal\content_deploy\Dump;

use Drupal\content_deploy\EntityDependencyResolverInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * The factory of DumpRestorer.
 */
class DumpRestorerFactory {

  private $entityTypeManager;

  private $entityFieldManager;

  private $fieldTypePluginManager;

  /**
   * DumpRestorerFactory constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, FieldTypePluginManagerInterface $fieldTypePluginManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypePluginManager = $fieldTypePluginManager;
  }

  /**
   * Instantiate the restorer.
   *
   * @param \Drupal\content_deploy\EntityDependencyResolverInterface $resolver
   *   The entity dependency resolver.
   *
   * @return \Drupal\content_deploy\Dump\DumpRestorer
   *   The instance.
   */
  public function get(EntityDependencyResolverInterface $resolver) {
    return new DumpRestorer(
      $resolver,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->fieldTypePluginManager
    );
  }

}
