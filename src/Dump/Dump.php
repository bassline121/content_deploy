<?php

namespace Drupal\content_deploy\Dump;

use Drupal\Core\Serialization\Yaml;

/**
 * Class Dump.
 *
 * @property-read string $dependencyName
 * @property-read string $entityTypeId
 * @property-read string $bundle
 * @property-read string $uuid
 * @property-read array $fields
 * @property-read Blob $blob
 * @property-read array[] $dependencies
 */
class Dump {

  /**
   * Gets all keys for the dependencies array.
   *
   * @return string[]
   *   An array of key.
   */
  public function getDependencyKeys() {
    $keys = array_keys($this->dependencies);
    return $keys;
  }

  /**
   * Gets the dependencies array for the key.
   *
   * @param string $key
   *   The dependency key.
   *
   * @return string[]
   *   The array of dependencies.
   */
  public function getDependenciesForKey($key) {
    $deps = $this->dependencies[$key] ?? [];
    return $deps;
  }

  /**
   * Converts to associated array.
   *
   * @return array
   *   An associated array of this dump.
   */
  public function toArray() {
    $values = [
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'uuid' => $this->uuid,
      'fields' => $this->fields,
      'dependencies' => $this->dependencies,
    ];

    if (!empty($this->blob)) {
      $values['blob'] = [
        'uri' => $this->blob->uri,
        'hash' => $this->blob->hash,
        'extension' => $this->blob->extension,
      ];
    }

    return $values;
  }

  /**
   * Converts to YAML.
   *
   * @return string
   *   A string of YAML representation.
   */
  public function toYaml() {
    $values = $this->toArray();
    $content = Yaml::encode($values);
    return $content;
  }

}
