<?php

namespace Drupal\content_deploy\Dump;

use Drupal\Component\Serialization\Yaml;

use Drupal\content_deploy\Utility;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class DumpBuilder.
 */
class DumpBuilder {

  /**
   * The building dump.
   *
   * @var Dump
   */
  private $dump;

  /**
   * The associated array of dependencies.
   *
   * @var array[]
   */
  private $dependenciesAssocByKey = [];

  /**
   * DumpBuilder constructor.
   */
  public function __construct() {
    $this->dump = new Dump();
  }

  /**
   * Sets a property to the building dump.
   */
  private function set($name, $value): self {
    $this->dump->{$name} = $value;
    return $this;
  }

  /**
   * Sets a entity type ID.
   */
  public function setEntityTypeId($entityTypeId): self {
    return $this->set('entityTypeId', $entityTypeId);
  }

  /**
   * Sets a bundle.
   */
  public function setBundle($bundle): self {
    return $this->set('bundle', $bundle);
  }

  /**
   * Sets a uuid.
   */
  public function setUuid($uuid): self {
    return $this->set('uuid', $uuid);
  }

  /**
   * Sets fields.
   */
  public function setFields(array $fields): self {
    return $this->set('fields', $fields);
  }

  /**
   * Sets a blob.
   */
  public function setBlob(Blob $blob): self {
    return $this->set('blob', $blob);
  }

  /**
   * Add a dependent entity.
   */
  public function addDependentEntity(EntityInterface $entity): self {
    $key = $entity->getConfigDependencyKey();
    $name = Utility::getEntityDependencyName($entity);
    $this->dependenciesAssocByKey[$key][$name] = TRUE;

    return $this;
  }

  /**
   * Gets the built dump.
   */
  public function get(): Dump {
    if (!isset($this->dump->dependencyName)) {
      $dep_name = "{$this->dump->entityTypeId}.{$this->dump->bundle}.{$this->dump->uuid}";
      $this->set('dependencyName', $dep_name);
    }

    if (!isset($this->dump->dependencies)) {
      $dependencies = array_map('array_keys', $this->dependenciesAssocByKey);
      $this->set('dependencies', $dependencies);
    }

    if (!isset($this->dump->blob)) {
      $this->set('blob', NULL);
    }

    return $this->dump;
  }

  /**
   * Loads the dump from the YAML file.
   *
   * @param string $yaml_path
   *   The path of YAML file.
   *
   * @return \Drupal\content_deploy\Dump\Dump|null
   *   A dump instance if file is valid.
   *   Otherwise NULL.
   */
  public function loadFile($yaml_path) {
    if (file_exists($yaml_path)) {
      $content = file_get_contents($yaml_path);
      return $this->loadYaml($content);
    }

    return NULL;
  }

  /**
   * Loads the dump from YAML content.
   *
   * @param string $content
   *   A content of YAML format.
   *
   * @return \Drupal\content_deploy\Dump\Dump
   *   The loaded dump.
   */
  public function loadYaml($content) {
    $values = Yaml::decode($content);
    return $this->load($values);
  }

  /**
   * Loads the dump from an associated array.
   *
   * @param array $values
   *   An associated array of source.
   *
   * @return \Drupal\content_deploy\Dump\Dump
   *   The loaded dump.
   */
  public function load(array $values) {
    $this
      ->setEntityTypeId($values['entity_type'])
      ->setBundle($values['bundle'])
      ->setUuid($values['uuid'])
      ->setFields($values['fields'])
      ->set('dependencies', $values['dependencies']);

    if (!empty($values['blob'])) {
      $this->setBlob(Blob::create($values['blob']));
    }

    return $this->get();
  }

}
