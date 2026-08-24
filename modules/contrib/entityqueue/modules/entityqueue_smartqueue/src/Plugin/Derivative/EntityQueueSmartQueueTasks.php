<?php

declare(strict_types=1);

namespace Drupal\entityqueue_smartqueue\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a local task on each entity that has a smartqueue.
 */
class EntityQueueSmartQueueTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteProviderInterface $routeProvider,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('router.route_provider'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    /** @var \Drupal\entityqueue\EntityQueueInterface[] $queues */
    $queues = $this->entityTypeManager->getStorage('entity_queue')
      ->loadByProperties(['handler' => 'smartqueue']);

    foreach ($queues as $queue) {
      $entity_type_id = $queue->getHandlerConfiguration()['entity_type'];
      $canonical = "entity.$entity_type_id.canonical";
      try {
        $this->routeProvider->getRouteByName($canonical);
      }
      catch (RouteNotFoundException) {
        continue;
      }

      $this->derivatives[$queue->id()] = [
        'title' => $this->t('@queue queue', ['@queue' => $queue->label()]),
        'route_name' => "entityqueue_smartqueue.{$queue->id()}",
        'base_route' => $canonical,
        'weight' => 100,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
