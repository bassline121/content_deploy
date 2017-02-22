<?php

namespace Drupal\content_deploy;

use Drupal\Core\Entity\EntityInterface;

/**
 * The utility for hash method.
 */
class Utility {

  /**
   * Creates hash for given file.
   *
   * @param string $filename
   *   A filename.
   *
   * @return string
   *   A hash string.
   */
  public static function hashFile($filename) {
    return sha1_file($filename);
  }

  /**
   * Creates hash for given string.
   *
   * @param string $string
   *   A string.
   *
   * @return string
   *   A hash string.
   */
  public static function hashString($string) {
    return sha1($string);
  }

  /**
   * Gets a dependency name of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity that is depended.
   *
   * @return string
   *   A dependency name.
   */
  public static function getEntityDependencyName(EntityInterface $entity) {
    $name = static::createEntityDependencyName($entity->getEntityTypeId(), $entity->bundle(), $entity->uuid());
    return $name;
  }

  /**
   * Creates an entity dependency name from components.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   * @param string $bundle
   *   A bundle.
   * @param string $uuid
   *   An uuid.
   *
   * @return string
   *   A dependency name.
   */
  public static function createEntityDependencyName($entity_type_id, $bundle, $uuid) {
    $name = "$entity_type_id:$bundle:$uuid";
    return $name;
  }

  /**
   * Parses and gets components of an entity dependency name.
   *
   * @param string $name
   *   A name.
   *
   * @return array|null
   *   An associated array of components if an name is valid.
   *   Otherwise NULL.
   *   A returned array has keys of "entity_type" and "bundle" and "uuid".
   */
  public static function parseContentEntityDependencyName($name) {
    $tokens = explode(':', $name);

    $components = [
      'entity_type' => $tokens[0] ?? NULL,
      'bundle' => $tokens[1] ?? NULL,
      'uuid' => $tokens[2] ?? NULL,
    ];
    return $components;
  }

  /**
   * Determines if the class is subclass of another class, or same class.
   *
   * The is_subclass_of() function returns FALSE if $class equals $super_class.
   * This function returns TRUE if the case like that.
   *
   * @param string $class
   *   The tested class name.
   * @param string $super_class
   *   The another class name.
   *
   * @return bool
   *   TRUE if the class is subclass.
   */
  public static function isSubclassOf($class, $super_class) {
    return ($class === $super_class) || is_subclass_of($class, $super_class);
  }

}
