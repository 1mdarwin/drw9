<?php

namespace Drupal\migrate_run;

use Drupal\migrate\MigrateMessageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Print message in drush from migrate message. Drush 9 version.
 *
 * @package Drupal\migrate_run
 */
class DrushLogMigrateMessage implements MigrateMessageInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * DrushLogMigrateMessage constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   */
  public function __construct(LoggerInterface $logger) {
    $this->setLogger($logger);
  }

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   */
  public function display($message, $type = 'status') {
    $type = ($type === 'status' ? 'notice' : $type);
    $this->logger->log($type, $message);
  }

}
