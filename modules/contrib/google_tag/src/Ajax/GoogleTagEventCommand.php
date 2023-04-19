<?php

declare(strict_types=1);

namespace Drupal\google_tag\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command for sending events to GA for ajax responses.
 */
final class GoogleTagEventCommand implements CommandInterface {

  /**
   * GoogleTagEventCommand constructor.
   *
   * @param string $event_name
   *   Event name.
   * @param array $data
   *   Event data.
   */
  public function __construct(
    private string $event_name,
    private array $data
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public function render() {
    return [
      'command' => 'gtagEvent',
      'event_name' => $this->event_name,
      'data' => $this->data,
    ];
  }

}
