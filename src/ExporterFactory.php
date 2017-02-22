<?php

namespace Drupal\content_deploy;

use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ExporterFactory.
 */
class ExporterFactory {

  /**
   * The config for the module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The dependency query.
   *
   * @var \Drupal\content_deploy\EntityDependencyQuery
   */
  private $query;

  /**
   * Exporter constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->config = $config_factory->get('content_deploy.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->query = new EntityDependencyQuery($entity_type_manager);
  }

  /**
   * Instantiates the export instance.
   *
   * @param string $destination
   *   The name of content directory.
   *
   * @return \Drupal\content_deploy\Exporter
   *   The instance.
   */
  public function get($destination = 'sync') {
    $storage = DumpStorage::create($destination, $this->config);

    return new Exporter(
      $destination,
      $this->config,
      $this->entityTypeManager,
      $storage,
      $this->query,
      $this->logger
    );
  }

}
