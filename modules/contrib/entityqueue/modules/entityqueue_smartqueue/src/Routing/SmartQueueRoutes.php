<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\entityqueue_smartqueue\Controller\SmartQueueRedirect;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Registers a redirect route for each smartqueue.
 *
 * Each route hangs off the target entity type's canonical route as a local
 * task and redirects to the matching subqueue's edit form. The route can't be
 * the subqueue edit form directly because that needs an entity_subqueue
 * parameter, which the canonical entity route doesn't carry.
 */
class SmartQueueRoutes extends RouteSubscriberBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    /** @var \Drupal\entityqueue\EntityQueueInterface[] $queues */
    $queues = $this->entityTypeManager->getStorage('entity_queue')
      ->loadByProperties(['handler' => 'smartqueue']);

    foreach ($queues as $queue) {
      $entity_type_id = $queue->getHandlerConfiguration()['entity_type'];

      // The redirect tab lives on the target entity's canonical route, so skip
      // entity types that don't have one.
      if (!$collection->get("entity.$entity_type_id.canonical")) {
        continue;
      }

      $route = new Route(
        "/admin/entityqueue-smartqueue/{$queue->id()}/{{$entity_type_id}}",
        [
          '_controller' => SmartQueueRedirect::class,
          'entityqueue_smartqueue_queue' => $queue->id(),
          'entityqueue_smartqueue_entity_type' => $entity_type_id,
        ],
        [
          '_entityqueue_smartqueue_subqueue' => 'TRUE',
        ],
        [
          'parameters' => [
            $entity_type_id => ['type' => "entity:$entity_type_id"],
          ],
        ],
      );

      $collection->add("entityqueue_smartqueue.{$queue->id()}", $route);
    }
  }

}
