<?php

namespace Drupal\content_deploy\Dump;

/**
 * Class BlobDump.
 *
 * @property-read string $uri
 * @property-read string $extension
 * @property-read string $hash
 */
class Blob {

  /**
   * Sets a property.
   */
  private function set($name, $value): self {
    $this->{$name} = $value;
    return $this;
  }

  /**
   * Create an instance from an associated array.
   */
  public static function create(array $values): Blob {
    $blob = (new Blob())
      ->set('uri', $values['uri'])
      ->set('hash', $values['hash'])
      ->set('extension', $values['extension']);
    return $blob;
  }

  /**
   * Create an instance from an uri & hash.
   */
  public static function createFromUri($uri, $hash): Blob {
    if (preg_match('@(\.[^/.]+)$@', $uri, $matches)) {
      $extension = $matches[1];
    }
    else {
      $extension = NULL;
    }

    $blob = (new Blob())
      ->set('uri', $uri)
      ->set('hash', $hash)
      ->set('extension', $extension);
    return $blob;
  }

}
