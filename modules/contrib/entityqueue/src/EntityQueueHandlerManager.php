<?php

declare(strict_types=1);

namespace Drupal\entityqueue;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an EntityQueueHandler plugin manager.
 */
class EntityQueueHandlerManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // Attribute-based plugin discovery is available in Drupal 10.3 and later.
    // On Drupal 10.2, only annotation discovery is supported.
    if (version_compare(\Drupal::VERSION, '10.3.0', '>=')) {
      parent::__construct('Plugin/EntityQueueHandler', $namespaces, $module_handler, NULL, 'Drupal\entityqueue\Attribute\EntityQueueHandler', 'Drupal\entityqueue\Annotation\EntityQueueHandler');
    }
    else {
      parent::__construct('Plugin/EntityQueueHandler', $namespaces, $module_handler, NULL, 'Drupal\entityqueue\Annotation\EntityQueueHandler');
    }

    $this->setCacheBackend($cache_backend, 'entityqueue_handler');
  }

  /**
   * Gets all handlers.
   *
   * @return array
   *   Returns all entityqueue handlers.
   */
  public function getAllEntityQueueHandlers() {
    $handlers = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_def) {
      $handlers[$plugin_id] = $plugin_def['title'];
    }
    asort($handlers);

    return $handlers;
  }

}
