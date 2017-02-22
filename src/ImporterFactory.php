<?php

namespace Drupal\content_deploy;

use Drupal\content_deploy\Dump\DumpRestorerFactory;
use Drupal\content_deploy\Dump\DumpStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ImporterFactory.
 */
class ImporterFactory {

  private $config;

  private $configFactory;

  private $restorerFactory;

  private $entityRepository;

  private $logger;

  /**
   * ImporterFactory constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DumpRestorerFactory $restorer, EntityRepository $entityRepository, LoggerInterface $logger) {
    $this->config = $config_factory->get('content_deploy.settings');
    $this->configFactory = $config_factory;
    $this->restorerFactory = $restorer;
    $this->entityRepository = $entityRepository;
    $this->logger = $logger;
  }

  /**
   * Instantiate the importer instance.
   *
   * @param string $source
   *   The key of the source content directory.
   * @param string[] $dep_names
   *   The array of an entity dep name to import.
   *
   * @return \Drupal\content_deploy\Importer
   *   The importer instance.
   */
  public function get($source, array $dep_names) {
    $storage = DumpStorage::create($source, $this->config);

    return new Importer(
      $source,
      $dep_names,
      $this->config,
      $this->configFactory,
      $this->restorerFactory,
      $this->entityRepository,
      $storage,
      $this->logger
    );
  }

}
