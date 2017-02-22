<?php

namespace Drupal\content_deploy\Exception;

/**
 * Class MissingDependencyException.
 */
class MissingDependencyException extends ContentDeployException {

  /**
   * MissingDependencyException constructor.
   */
  public function __construct($dep_name, $message = "", $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = "Dependency $dep_name is missing.";
    }

    parent::__construct($message, $code, $previous);
  }

}
