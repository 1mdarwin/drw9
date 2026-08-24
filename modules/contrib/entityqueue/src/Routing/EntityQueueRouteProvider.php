<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the entity_queue entity type.
 */
class EntityQueueRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getEditFormRoute($entity_type);

    // The edit form changes a queue's configuration, which is a separate,
    // administrative action from managing its items. Keep it on the configure
    // operation so users who can only manipulate items can't reach it.
    $route?->setRequirement('_entity_access', 'entity_queue.configure');

    return $route;
  }

}
