<?php
// @codingStandardsIgnoreFile

namespace Drupal\content_deploy\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * ConfigForm constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->config = $this->config('content_deploy.settings');
  }

  /* @noinspection PhpMissingParentCallCommonInspection */

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_deploy_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_deploy.settings'];
  }

  /* @noinspection PhpMissingParentCallCommonInspection */

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /*
    $form['exports'] = [
      '#type' => 'checkboxes',
      '#title' => 'Entity types to export',
      '#options' => $this->getExportableEntityTypes(),
    ];
     */

    $form['settings'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#value' => Html::escape(print_r($this->config->getRawData(), TRUE)),
    ];

    return $form;
  }

  /** @noinspection PhpUnusedPrivateMethodInspection */
  private function getExportableEntityTypes() {
    $entity_types = [];

    foreach ($this->entityTypeManager->getDefinitions() as $id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_types[$id] = $entity_type->getLabel() . ' (' . $id . ')';
      }
    }

    asort($entity_types);

    return $entity_types;
  }

  /* @noinspection PhpMissingParentCallCommonInspection */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
