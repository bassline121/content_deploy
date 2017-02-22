<?php

namespace Drupal\content_deploy;

use Drupal\Component\Diff\Diff;
use Drupal\content_deploy\Dump\Dumper;
use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DiffGenerator.
 */
class DiffGenerator {

  use StringTranslationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\content_deploy\EntityRepository
   */
  private $entityRepository;

  /**
   * The dump storage.
   *
   * @var \Drupal\content_deploy\Dump\DumpStorage
   */
  private $storage;

  /**
   * The dumper.
   *
   * @var \Drupal\content_deploy\Dump\Dumper
   */
  private $dumper;

  /**
   * ContentDeployForm constructor.
   */
  public function __construct(EntityRepository $entity_repository, DumpStorage $storage) {
    $this->entityRepository = $entity_repository;
    $this->storage = $storage;
    $this->dumper = new Dumper();
  }

  /**
   * Gets the diff of contents between active to staged.
   *
   * @return \Drupal\Component\Diff\Diff[]
   *   The array of diffs.
   *   The key is a dependency name of an entity.
   *   The value is a diff object.
   */
  public function diff() {
    $diffs = [];

    $dep_names = $this->storage->listAll();

    foreach ($dep_names as $dep_name) {
      $diff = $this->diffSingle($dep_name);
      if ($diff) {
        $diffs[$dep_name] = $diff;
      }
    }

    return $diffs;
  }

  /**
   * Gets a diff of the entity.
   *
   * @param string $dep_name
   *   The dependency name of the entity.
   *
   * @return \Drupal\Component\Diff\Diff|null
   *   A diff object or NULL.
   */
  private function diffSingle($dep_name) {
    $active_entity = $this->entityRepository->loadEntityByDependencyName($dep_name);
    $active_dump = $active_entity ? $this->dumper->dump($active_entity) : NULL;

    $staged_dump = $this->storage->load($dep_name);

    $diff = $this->diffBetweenDumps($active_dump, $staged_dump);
    return $diff;
  }

  /**
   * Diff constructor.
   *
   * @param \Drupal\content_deploy\Dump\Dump|null $active
   *   The dump of active entity or NULL.
   * @param \Drupal\content_deploy\Dump\Dump|null $staging
   *   The staged dump or NULL.
   *
   * @return \Drupal\Component\Diff\Diff|null
   *   The diff object if exist differences.
   *   Otherwise NULL.
   */
  private function diffBetweenDumps($active, $staging) {
    if ($active === NULL && $staging === NULL) {
      return NULL;
    }

    $content_active = $active ? $active->toYaml() : $this->t('Entity added');
    $content_staging = $staging ? $staging->toYaml() : $this->t('Entity deleted');

    if ($content_active === $content_staging) {
      // Same content.
      return NULL;
    }

    $diff = new Diff(
      explode("\n", $content_active),
      explode("\n", $content_staging)
    );
    return $diff;
  }

}
