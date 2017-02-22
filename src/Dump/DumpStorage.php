<?php

namespace Drupal\content_deploy\Dump;

use Drupal\content_deploy\Exception\FileSystemException;
use Drupal\Core\Config\Config;

/**
 * Class DumpStorage.
 */
class DumpStorage {

  /**
   * The base path of paths that will be generated.
   *
   * Ends with a slash if that is not empty.
   *
   * @var string
   */
  private $basePath;

  /**
   * Instantiate an instance from the config.
   *
   * @param string $key
   *   The key of the content directory.
   * @param \Drupal\Core\Config\Config $config
   *   The 'content_deploy.settings' config.
   *
   * @return static
   *   The instance.
   */
  public static function create($key, Config $config) {
    $base_path = $config->get('directories')[$key] ?? '';
    $instance = new static($base_path);
    return $instance;
  }

  /**
   * DumpStorage constructor.
   */
  public function __construct($base_path) {
    if (!empty($base_path) && $base_path[strlen($base_path) - 1] !== '/') {
      $base_path .= '/';
    }

    $this->basePath = $base_path;
  }

  /**
   * Gets an array of a dependency name of all existing dumps.
   *
   * @return string[]
   *   An array of a dependency name.
   */
  public function listAll() {
    $dep_names = [];

    $dir = scandir($this->basePath);

    foreach ($dir as $filename) {
      $dep_name = static::getDependencyNameFromDumpPath($filename);
      if ($dep_name) {
        $dep_names[] = $dep_name;
      }
    }

    return $dep_names;
  }

  /**
   * Loads the dump from the dependency name.
   *
   * @param string $dep_name
   *   The dependency name.
   *
   * @return Dump|null
   *   A dump instance if the dump exists.
   *   Otherwise NULL.
   */
  public function load($dep_name) {
    $dumps = $this->loadMultiple([$dep_name]);
    $dump = !empty($dumps) ? reset($dumps) : NULL;
    return $dump;
  }

  /**
   * Loads multiple dumps from the dependency name.
   *
   * @param string[] $dep_names
   *   The dependency name.
   *
   * @return Dump[]
   *   An array of an existing dump instance.
   *   That does not contain non-existing dumps.
   */
  public function loadMultiple(array $dep_names) {
    $dumps = [];

    foreach ($dep_names as $dep_name) {
      $path = $this->getDumpPath($dep_name);
      $dump = (new DumpBuilder())->loadFile($path);
      if ($dump) {
        $dumps[] = $dump;
      }
    }

    return $dumps;
  }

  /**
   * Saves the dump (and the blob) to the file storage.
   *
   * @param \Drupal\content_deploy\Dump\Dump $dump
   *   The dump.
   *
   * @throws \Drupal\content_deploy\Exception\FileSystemException
   *   Cannot save due to the file system.
   */
  public function save(Dump $dump) {
    $dep_name = $dump->dependencyName;
    $path = $this->getDumpPath($dep_name);

    $this->ensureDirectory($path);

    if (file_put_contents($path, $dump->toYaml()) === FALSE) {
      throw new FileSystemException("Cannot create a dump file $path.");
    }

    // Copy the file blob if exists.
    if ($dump->blob) {
      $blob_path = $this->getBlobPath($dep_name, $dump->blob);

      if (copy($dump->blob->uri, $blob_path) === FALSE) {
        throw new FileSystemException("Cannot copy a blob file from {$dump->blob->uri} to $blob_path.");
      }
    }
  }

  /**
   * Ensures to parent directories exists.
   *
   * @param string $file_path
   *   A file path.
   *
   * @throws FileSystemException
   *   Cannot create the directory.
   */
  private function ensureDirectory($file_path) {
    $dir = dirname($file_path);

    if (!file_exists($dir)) {
      if (!mkdir($dir, 0777, TRUE)) {
        throw new FileSystemException("Cannot create the directory $dir.");
      }
    }
  }

  /**
   * Gets the dump file path from the dependency name.
   *
   * @param string $dep_name
   *   The dependency name.
   *
   * @return string
   *   The dump path.
   */
  private function getDumpPath($dep_name) {
    $path = $this->basePath . static::getBasenameFromDependencyName($dep_name) . '.yml';
    return $path;
  }

  /**
   * Gets the path for the blob of the entity.
   *
   * @param string $dep_name
   *   The dependency name.
   * @param Blob $blob
   *   The blob.
   *
   * @return string
   *   The dump path.
   */
  public function getBlobPath($dep_name, Blob $blob) {
    $blob_extension = '.blob' . ($blob->extension ?? '');
    $path = $this->basePath . static::getBasenameFromDependencyName($dep_name) . $blob_extension;
    return $path;
  }

  /**
   * Extracts the dependency name of the dump file path.
   *
   * @param string $path
   *   The dump file path.
   *
   * @return string|null
   *   The dependency name if the path is the valid dump path.
   *   Otherwise NULL.
   */
  private static function getDependencyNameFromDumpPath($path) {
    $basename = basename($path);

    if (preg_match('@^([^.]+\.[^.]+\.[^.]+).yml$@', $basename, $matches)) {
      $dep_name = static::getDependencyNameFromBasename($matches[1]);
      return $dep_name;
    }

    return NULL;
  }

  /**
   * Gets the dependency name from the file basename.
   */
  private static function getDependencyNameFromBasename(string $basename): string {
    $dep_name = str_replace('.', ':', $basename);
    return $dep_name;
  }

  /**
   * Gets the basename from the dependency name.
   */
  private static function getBasenameFromDependencyName(string $dep_name): string {
    $basename = str_replace(':', '.', $dep_name);
    return $basename;
  }

}
