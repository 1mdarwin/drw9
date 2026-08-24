<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an EntityQueueHandler attribute object.
 *
 * Plugin Namespace: Plugin\EntityQueueHandler.
 *
 * @see \Drupal\entityqueue\EntityQueueHandlerInterface
 * @see \Drupal\entityqueue\EntityQueueHandlerManager
 * @see \Drupal\entityqueue\EntityQueueHandlerBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityQueueHandler extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
