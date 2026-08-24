<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Grants access to a smartqueue redirect route.
 *
 * Access mirrors the subqueue's own edit-form access, so the local task and the
 * entity operation only show up when the user can actually manage the queue.
 */
final class SubqueueAccessCheck implements AccessInterface {

  /**
   * Checks access to the smartqueue redirect route.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $route = $route_match->getRouteObject();
    $entity_type_id = $route->getDefault('entityqueue_smartqueue_entity_type');
    $queue_id = $route->getDefault('entityqueue_smartqueue_queue');

    $entity = $route_match->getParameter($entity_type_id);
    if (!$entity instanceof EntityInterface) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $subqueue = EntitySubqueue::load($queue_id . '__' . $entity->id());
    if ($subqueue === NULL) {
      // Subqueues come and go with the attached entity, so don't cache a miss.
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }

    return $subqueue->access('update', $account, TRUE);
  }

}
