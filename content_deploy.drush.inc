<?php

/**
 * @file
 * Defines drush commands.
 */

use Drupal\content_deploy\Logger\ConsoleLog;

/**
 * Gets drush command definitions.
 */
function content_deploy_drush_command() {
  $items = [];

  $items['content-export'] = [
    'description' => 'Export contents.',
  ];

  return $items;
}

/**
 * Initialize logger.
 */
function _content_deploy_init_logger() {
  $logger = new ConsoleLog('content_deploy');
  \Drupal::getContainer()->set('logger.channel.content_deploy', $logger);
}

/**
 * The content-export command callback.
 */
function drush_content_deploy_content_export($destination = 'sync') {
  _content_deploy_init_logger();

  /** @var \Drupal\content_deploy\ExporterFactory $factory */
  $factory = \Drupal::service('content_deploy.exporter.factory');

  $exporter = $factory->get($destination);
  $exporter->export();
}
