<?php

declare(strict_types=1);

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading EntityQueueHandler plugins.
 */
class EntityQueueHandlerPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The entity queue that is using this plugin collection.
   *
   * @var \Drupal\entityqueue\Entity\EntityQueue
   */
  protected $queue;

  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, EntityQueueInterface $queue) {
    $this->queue = $queue;

    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    $this->pluginInstances[$instance_id]->setQueue($this->queue);
  }

}
