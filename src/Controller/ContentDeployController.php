<?php

namespace Drupal\content_deploy\Controller;

use Drupal\content_deploy\Form\ImportForm;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class ContentDeployController.
 */
class ContentDeployController extends ControllerBase {

  /**
   * Build the diff page.
   *
   * @return array
   *   An elements array.
   */
  public function diff() {
    return $this->formBuilder()->getForm(ImportForm::class);
  }

}
