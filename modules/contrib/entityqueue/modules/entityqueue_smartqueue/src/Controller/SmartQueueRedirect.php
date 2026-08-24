<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirects from a smartqueue route to the matching subqueue edit form.
 */
final class SmartQueueRedirect extends ControllerBase {

  /**
   * Redirects to the subqueue attached to the route's entity.
   */
  public function __invoke(RouteMatchInterface $route_match): RedirectResponse {
    $route = $route_match->getRouteObject();
    $entity_type_id = $route->getDefault('entityqueue_smartqueue_entity_type');
    $queue_id = $route->getDefault('entityqueue_smartqueue_queue');

    $entity = $route_match->getParameter($entity_type_id);
    $subqueue = EntitySubqueue::load($queue_id . '__' . $entity->id());
    if ($subqueue === NULL) {
      throw new NotFoundHttpException();
    }

    return new RedirectResponse($subqueue->toUrl('edit-form')->toString());
  }

}
