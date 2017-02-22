<?php

namespace Drupal\content_deploy\Dump;

use Drupal\content_deploy\Utility;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\file\Entity\File;

/**
 * Class Dumper.
 */
class Dumper {

  /**
   * The current processing dump builder.
   *
   * @var \Drupal\content_deploy\Dump\DumpBuilder|null
   */
  private $dumpBuilder;

  /**
   * Creates dump of entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity.
   *
   * @return Dump
   *   A dump.
   */
  public function dump(ContentEntityInterface $entity) {
    $this->dumpBuilder = new DumpBuilder();

    $this->dumpBuilder
      ->setEntityTypeId($entity->getEntityTypeId())
      ->setBundle($entity->bundle())
      ->setUuid($entity->uuid())
      ->setFields($this->getEntityFieldValues($entity));

    if ($entity instanceof File) {
      $uri = $entity->getFileUri();
      $blob = Blob::createFromUri($uri, Utility::hashFile($uri));
      $this->dumpBuilder->setBlob($blob);
    }

    $dump = $this->dumpBuilder->get();

    $this->dumpBuilder = NULL;

    return $dump;
  }

  /**
   * Gets fields of an entity.
   *
   * @param ContentEntityInterface $entity
   *   An entity.
   *
   * @return array
   *   An associated array of fields.
   */
  private function getEntityFieldValues(ContentEntityInterface $entity) {
    $fields = [];

    $bundle_key = $entity->getEntityType()->getKey('bundle');
    if ($bundle_key) {
      // Set 'bundle' field explicitly as string.
      // $entity->bundle->getValue() returns array, but it will fails on import.
      $fields[$bundle_key] = $entity->bundle();
    }

    foreach ($this->listDumpedFieldNames($entity) as $field_name) {
      $list = $entity->get($field_name);
      $fields[$field_name] = $this->getFieldValue($list);
    }

    return $fields;
  }

  /**
   * Lists field names that contains in dump.
   *
   * Not contain following fields in dump:
   *
   * - id
   * - revision_id
   * - bundle
   * - created
   * - changed
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity.
   *
   * @return string[]
   *   An array of field names.
   *   That is sorted.
   */
  private function listDumpedFieldNames(ContentEntityInterface $entity) {
    $dumped_field_names = [];

    $entity_type = $entity->getEntityType();

    $skipped_fields_map = array_flip([
      $entity_type->getKey('id'),
      $entity_type->getKey('revision'),
      $entity_type->getKey('bundle'),
    ]);

    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if (!isset($skipped_fields_map[$field_name])
        && $this->isFieldDumped($definition)
      ) {
        $dumped_field_names[] = $field_name;
      }
    }

    sort($dumped_field_names);

    return $dumped_field_names;
  }

  /**
   * Determines if a field is dumped.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   A field definition.
   *
   * @return bool
   *   TRUE if a field is dumped.
   *   Otherwise FALSE.
   */
  private function isFieldDumped(FieldDefinitionInterface $field_definition) {
    $type = $field_definition->getType();
    switch ($type) {
      case 'created':
      case 'changed':
        return FALSE;

      default:
        return TRUE;
    }
  }

  /**
   * Gets the field value of the entity.
   *
   * @param FieldItemListInterface $list
   *   The original field value.
   *
   * @return mixed
   *   The field value.
   */
  private function getFieldValue(FieldItemListInterface $list) {
    if ($list instanceof EntityReferenceFieldItemListInterface) {
      $value = $this->processEntityReferenceFieldItemList($list);
    }
    else {
      $value = $this->simplifyFieldValue($list, $list->getValue());
    }

    return $value;
  }

  /**
   * Simplifies the field value.
   *
   * Before:
   *
   * ```
   * $value == [
   *   // Has only 1 item.
   *   [
   *     // Has only the main property.
   *     'value' => 'SIMPLE_VALUE',
   *   ],
   * ];
   * ```
   *
   * After:
   *
   * ```
   * $value == 'SIMPLE_VALUE';
   * ```
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $orig_list
   *   The original field value.
   * @param array $list
   *   The retrieved field value from the $orig_value.
   *
   * @return mixed
   *   The simplified value.
   */
  private function simplifyFieldValue(FieldItemListInterface $orig_list, array $list) {
    if (count($list) === 1
      && is_array($item = reset($list)) && count($item) === 1
      && ($item_definition = $orig_list->getItemDefinition())
      && $item_definition instanceof FieldItemDataDefinition
    ) {
      $main_property = $item_definition->getMainPropertyName();
      if (array_key_exists($main_property, $item)) {
        $simple_value = $item[$main_property];
        return $simple_value;
      }
    }

    // Cannot simplify.
    return $list;
  }

  /**
   * Processes and gets The entity reference field value.
   *
   * 1. Resolve entity reference to UUID.
   * 2. Add dependencies to dump.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $orig_list
   *   The original field item list.
   *
   * @return array
   *   The array of processed item.
   */
  private function processEntityReferenceFieldItemList(EntityReferenceFieldItemListInterface $orig_list) {
    $list = [];

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $orig_item */
    foreach ($orig_list as $orig_item) {
      $item = $orig_item->toArray();

      if (isset($orig_item->entity)) {
        $target_entity = $orig_item->entity;

        // Replace target_id with entity.
        unset($item['target_id']);
        $item['entity'] = Utility::getEntityDependencyName($target_entity);

        $this->dumpBuilder->addDependentEntity($target_entity);
      }

      $list[] = $item;
    }

    return $list;
  }

}
