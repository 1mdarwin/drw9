<?php

declare(strict_types=1);

namespace Drupal\entityqueue\RouteProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\entityqueue\EntitySubqueueInterface;
use Symfony\Component\Routing\Route;

/**
 * Fills in the 'entity_queue' parameter for subqueue routes.
 *
 * Subqueue routes nest under their queue, so their paths carry both an
 * 'entity_subqueue' and an 'entity_queue' parameter. Code that builds these
 * URLs from just the subqueue (local tasks added by other modules, contextual
 * links, etc.) only has the 'entity_subqueue' value, which makes URL
 * generation fail with a missing 'entity_queue' parameter. Derive it from the
 * subqueue's bundle here so any URL generation path works, not just
 * EntitySubqueue::toUrl().
 */
class EntitySubqueueRouteProcessor implements OutboundRouteProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    // Only act on subqueue routes that need 'entity_queue' but don't have it.
    if (empty($parameters['entity_subqueue']) || !empty($parameters['entity_queue']) || !str_contains($route->getPath(), '{entity_queue}')) {
      return;
    }

    $subqueue = $parameters['entity_subqueue'];
    if ($subqueue instanceof EntitySubqueueInterface) {
      $parameters['entity_queue'] = $subqueue->bundle();
    }
    elseif (is_string($subqueue) && ($subqueue = $this->entityTypeManager->getStorage('entity_subqueue')->load($subqueue))) {
      $parameters['entity_queue'] = $subqueue->bundle();
    }
  }

}
