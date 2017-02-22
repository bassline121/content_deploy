<?php

namespace Drupal\content_deploy\Dump;

use Drupal\content_deploy\EntityDependencyResolverInterface;
use Drupal\content_deploy\Utility;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem as EntityReferenceFieldType;

/**
 * Class DumpRestorer.
 */
class DumpRestorer {

  /**
   * The entity dependency resolver.
   *
   * @var EntityDependencyResolverInterface
   */
  private $resolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  private $fieldTypePluginManager;

  /**
   * DumpRestorer constructor.
   */
  public function __construct(EntityDependencyResolverInterface $resolver, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, FieldTypePluginManagerInterface $fieldTypePluginManager) {
    $this->resolver = $resolver;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypePluginManager = $fieldTypePluginManager;
  }

  /**
   * Gets all importable field values from the dump.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   The dump.
   *
   * @return array
   *   The array of field values.
   */
  public function getImportableFields(Dump $dump) {
    $fields = [];

    $entity_type = $this->entityTypeManager->getDefinition($dump->entityTypeId);

    $keys_map = array_flip($entity_type->getKeys());

    $definitions = $this->entityFieldManager->getFieldDefinitions($dump->entityTypeId, $dump->bundle);

    foreach ($dump->fields as $field_name => $dump_value) {
      if (isset($keys_map[$field_name])) {
        $fields[$field_name] = $dump_value;
      }
      else {
        $definition = $definitions[$field_name];
        $value = $this->getImportableFieldValue($definition, $dump_value);
        $fields[$field_name] = $value;
      }
    }

    return $fields;
  }

  /**
   * Gets the importable field value from the dump value.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The definition of the field.
   * @param array|mixed $dump_value
   *   The dumped field value.
   *
   * @return array|mixed
   *   The transformed field value.
   */
  private function getImportableFieldValue(FieldDefinitionInterface $definition, $dump_value) {
    $type = $definition->getType();
    // The class name of type implements FieldItemInterface.
    $type_class = $this->fieldTypePluginManager->getPluginClass($type);

    if (Utility::isSubclassOf($type_class, EntityReferenceFieldType::class)) {
      $resolved_value = $this->processEntityReferenceFieldValue((array) $dump_value);
      return $resolved_value;
    }

    return $dump_value;
  }

  /**
   * Processes the dumped entity reference field and gets importable.
   *
   * @param array $dump_item_list
   *   The dumped field value.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The array of referenced entities that are resolved.
   */
  private function processEntityReferenceFieldValue(array $dump_item_list) {
    $list = [];

    foreach ($dump_item_list as $dump_item) {
      $item = $dump_item;

      if (isset($dump_item['entity'])) {
        // Overwrite with a resolved entity instance.
        $item['entity'] = $this->resolver->resolveEntityDependency($dump_item['entity']);
      }

      $list[] = $item;
    }

    return $list;
  }

}
