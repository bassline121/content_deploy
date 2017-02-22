<?php

namespace Drupal\content_deploy\Logger;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\RfcLogLevel;
use Drush\Log\LogLevel;

/**
 * The logger to output logs to drush CLI.
 */
class ConsoleLog extends LoggerChannel {

  const RFC_LOG_LEVELS = [
    // Psr log levels:
    LogLevel::EMERGENCY => RfcLogLevel::EMERGENCY,
    LogLevel::ALERT => RfcLogLevel::ALERT,
    LogLevel::CRITICAL => RfcLogLevel::CRITICAL,
    LogLevel::ERROR => RfcLogLevel::ERROR,
    LogLevel::WARNING => RfcLogLevel::WARNING,
    LogLevel::NOTICE => RfcLogLevel::NOTICE,
    LogLevel::INFO => RfcLogLevel::INFO,
    LogLevel::DEBUG => RfcLogLevel::DEBUG,

    // Drush log levels:
    LogLevel::BOOTSTRAP => RfcLogLevel::NOTICE,
    LogLevel::PREFLIGHT => RfcLogLevel::NOTICE,
    LogLevel::CANCEL => RfcLogLevel::WARNING,
    LogLevel::OK => RfcLogLevel::NOTICE,
    LogLevel::SUCCESS => RfcLogLevel::NOTICE,
    LogLevel::DEBUG_NOTIFY => RfcLogLevel::NOTICE,
    LogLevel::BATCH => RfcLogLevel::NOTICE,
  ];

  /**
   * DrushLog constructor.
   *
   * @throws \Exception
   *   A 'drush_log' function does not exist.
   */
  public function __construct($channel) {
    parent::__construct($channel);

    if (!function_exists('drush_log')) {
      throw new \LogicException('DrushLog can only use in Drush context.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $rfc_level = $level;
    if (is_string($rfc_level)) {
      $rfc_level = static::RFC_LOG_LEVELS[$rfc_level];
    }

    parent::log($rfc_level, $message, $context);

    drush_log($message, $level);
  }

}
