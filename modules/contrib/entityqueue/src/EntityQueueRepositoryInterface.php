<?php

declare(strict_types=1);

namespace Drupal\entityqueue;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides repository helpers for entity queues.
 *
 * This interface defines methods to discover queues that can hold a given
 * entity.
 */
interface EntityQueueRepositoryInterface {

  /**
   * Gets a list of queues which can hold this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface[]
   *   An array of entity queues which can hold this entity.
   */
  public function getAvailableQueuesForEntity(EntityInterface $entity);

}
