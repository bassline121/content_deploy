<?php

namespace Drupal\content_deploy\Form;

use Drupal\Component\Diff\Diff;
use Drupal\content_deploy\DiffGenerator;
use Drupal\content_deploy\Dump\Dumper;
use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\content_deploy\EntityRepository;
use Drupal\content_deploy\Exception\MissingDependencyException;
use Drupal\content_deploy\Importer;
use Drupal\content_deploy\ImporterFactory;
use Drupal\Core\Config\Config;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImportForm.
 */
class ImportForm extends FormBase {

  const CONTENT_SOURCE_DIRECTORY = 'sync';

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  private $diffFormatter;

  /**
   * The diff generator.
   *
   * @var \Drupal\content_deploy\DiffGenerator
   */
  private $diffGenerator;

  /**
   * The dumper.
   *
   * @var \Drupal\content_deploy\Dump\Dumper
   */
  private $dumper;

  /**
   * The importer factory.
   *
   * @var \Drupal\content_deploy\ImporterFactory
   */
  private $importerFactory;

  /* @noinspection PhpMissingParentCallCommonInspection */

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('content_deploy.settings'),
      $container->get('diff.formatter'),
      $container->get('content_deploy.entity.repository'),
      $container->get('content_deploy.importer.factory')
    );
  }

  /**
   * ContentDeployForm constructor.
   */
  public function __construct(Config $config, DiffFormatter $diff_formatter, EntityRepository $entity_repository, ImporterFactory $importer_factory) {
    $this->config = $config;
    $this->diffFormatter = $diff_formatter;
    $this->importerFactory = $importer_factory;
    $storage = DumpStorage::create(static::CONTENT_SOURCE_DIRECTORY, $config);
    $this->diffGenerator = new DiffGenerator($entity_repository, $storage);
    $this->dumper = new Dumper();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_deploy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $diffs = $this->diffGenerator->diff();

    if (!empty($diffs)) {
      $dep_names = array_keys($diffs);
      $form_state->addBuildInfo('entity_dep_names', $dep_names);

      $form['list'] = $this->buildDiffList($diffs);
    }
    else {
      $build['message'] = [
        '#markup' => $this->t('All staged contents are active.'),
      ];
    }

    return $form;
  }

  /**
   * Builds diff list element.
   *
   * @param \Drupal\Component\Diff\Diff[] $diffs
   *   The array of diffs.
   *
   * @return array
   *   A built element array.
   */
  private function buildDiffList(array $diffs) {
    $build = [];

    $build['list'] = [
      '#type' => 'container',
    ];

    $this->diffFormatter->show_header = FALSE;
    $build['#attached']['library'][] = 'system/diff';

    foreach ($diffs as $dep_name => $diff) {
      $build['list'][] = $this->buildDiff($dep_name, $diff);
    }

    $build['actions'] = ['#type' => 'actions'];

    $build['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Import'),
    ];

    return $build;
  }

  /**
   * Builds the diff element of the entity.
   *
   * @param string $dep_name
   *   The dependency name of the entity.
   * @param \Drupal\Component\Diff\Diff $diff
   *   The diff object.
   *
   * @return array
   *   The built element array.
   */
  private function buildDiff($dep_name, Diff $diff) {
    $build = [];

    $build['header'] = [
      '#type' => 'checkbox',
      '#title' => $dep_name,
    ];

    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
      ],
      '#header' => [
        ['data' => t('Active'), 'colspan' => '2'],
        ['data' => t('Staged'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dep_names = $form_state->getBuildInfo()['entity_dep_names'];

    // TODO: batch.
    $importer = $this->importerFactory->get(static::CONTENT_SOURCE_DIRECTORY, $dep_names);

    try {
      $counts = $importer->import();

      if (0 < $counts[Importer::RESULT_CREATED]) {
        drupal_set_message($this->formatPlural(
          $counts[Importer::RESULT_CREATED],
          '1 entity created.',
          '@count entities created.'));
      }

      if (0 < $counts[Importer::RESULT_UPDATED]) {
        drupal_set_message($this->formatPlural(
          $counts[Importer::RESULT_UPDATED],
          '1 entity updated.',
          '@count entities updated.'));
      }
    }
    catch (MissingDependencyException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
