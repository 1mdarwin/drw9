<?php

declare(strict_types=1);

namespace Drupal\entityqueue;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Provides an interface for an EntityQueueHandler plugin.
 *
 * @see \Drupal\entityqueue\Annotation\EntityQueueHandler
 * @see \Drupal\entityqueue\EntityQueueHandlerManager
 * @see \Drupal\entityqueue\EntityQueueHandlerBase
 * @see plugin_api
 */
interface EntityQueueHandlerInterface extends PluginFormInterface, ConfigurableInterface, PluginInspectionInterface, DerivativeInspectionInterface, DependentPluginInterface {

  /**
   * Sets the entity queue that is using this plugin.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue.
   *
   * @return $this
   */
  public function setQueue(EntityQueueInterface $queue);

  /**
   * Whether or not the handler supports multiple subqueues.
   *
   * @return bool
   *   TRUE if handler supports multiple subqueues, FALSE otherwise.
   */
  public function supportsMultipleSubqueues();

  /**
   * Whether or not the handler contains subqueues with an automated lifecycle.
   *
   * For example, this property controls whether the title of subqueues can be
   * edited, or if they can be created or deleted through the UI or API calls.
   *
   * @return bool
   *   TRUE if handler has automated subqueues, FALSE otherwise.
   */
  public function hasAutomatedSubqueues();

  /**
   * Builds the machine name (ID) for a subqueue of this queue.
   *
   * Called when a subqueue is saved without a name, so the handler can name it
   * according to its own scheme. Simple queues use the queue ID; queues with
   * user-managed subqueues derive a unique name from the title.
   *
   * @param \Drupal\entityqueue\EntitySubqueueInterface $subqueue
   *   The subqueue that needs a machine name.
   *
   * @return string
   *   The machine name to use as the subqueue ID.
   */
  public function getSubqueueName(EntitySubqueueInterface $subqueue): string;

  /**
   * Gets this queue handler's list builder operations.
   *
   * @return array
   *   An array of entity operations, as defined by
   *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations()
   */
  public function getQueueListBuilderOperations();

  /**
   * Gets the primary operation for managing this queue's items.
   *
   * This mirrors the primary action from ::getQueueListBuilderOperations():
   * queues with multiple subqueues point to the subqueue list, while simple
   * queues point directly to their single subqueue.
   *
   * @return array
   *   An array with 'title', 'url' (a \Drupal\Core\Url) and 'weight' keys.
   */
  public function getItemsOperation();

  /**
   * Acts on an entity queue before the presave hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePreSave(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on an entity queue before the insert or update hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param bool $update
   *   TRUE if the queue has been updated, or FALSE if it has been inserted.
   */
  public function onQueuePostSave(EntityQueueInterface $queue, EntityStorageInterface $storage, $update = TRUE);

  /**
   * Acts on entity queues before they are deleted and before hooks are invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePreDelete(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on deleted entity queues before the delete hook is invoked.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePostDelete(EntityQueueInterface $queue, EntityStorageInterface $storage);

  /**
   * Acts on loaded entity queues.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $queue
   *   The entity queue object.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   */
  public function onQueuePostLoad(EntityQueueInterface $queue, EntityStorageInterface $storage);

}
