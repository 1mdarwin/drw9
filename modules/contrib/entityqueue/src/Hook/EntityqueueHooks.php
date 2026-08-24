<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\EntityQueueInterface;
use Drupal\entityqueue\EntitySubqueueInterface;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for the Entityqueue module.
 */
class EntityqueueHooks {

  use StringTranslationTrait;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteMatchInterface $routeMatch,
    protected LocalTaskManagerInterface $localTaskManager,
  ) {}

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    // Only allow edit access on a subqueue title field if the queue doesn't
    // have automated subqueues.
    if ($operation == 'edit' && $field_definition->getName() == 'title' && $items && $items->getEntity()->getEntityTypeId() === 'entity_subqueue') {
      /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
      $queue = $items->getEntity()->getQueue();
      return AccessResult::forbiddenIf($queue->getHandlerPlugin()->hasAutomatedSubqueues());
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_views_pre_render().
   *
   * Add contextual links to views before rendering.
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view) {
    // Allow to disable the contextual links.
    if (!$view->display_handler->getOption('show_admin_links')) {
      return;
    }

    // Get view display relationships.
    $relationships = $view->relationship;
    foreach ($relationships as $relationship) {
      if ($relationship->field == 'entityqueue_relationship') {
        // Keep only the selected queues; a checkboxes element stores unchecked
        // options as falsy values, which must not be treated as a queue.
        $referenced_subqueues = array_filter((array) ($relationship->options['limit_queue'] ?? []));

        // Contextual links can handle only one set of links coming from a
        // module, so we'll have to settle for the first referenced queue.
        if (!empty($referenced_subqueues) && ($subqueue = EntitySubqueue::load(reset($referenced_subqueues)))) {
          $route_parameters = [
            'entity_queue' => $subqueue->getQueue()->id(),
            'entity_subqueue' => $subqueue->id(),
          ];
          $view->element['#contextual_links']['entityqueue'] = [
            'route_parameters' => $route_parameters,
          ];
        }
      }
    }

    // Add contextual link when view row plugin is entity_subqueue,
    // and entity queue filter are provided.
    if ($view->rowPlugin && $view->rowPlugin->getPluginId() == 'entity:entity_subqueue' && $view->rowPlugin->getEntityTypeId() == 'entity_subqueue') {
      $view_filters = $view->filter;
      foreach ($view_filters as $filter) {
        if ($filter->field == 'queue' && !empty($filter->value)) {
          if ($subqueue = EntitySubqueue::load(reset($filter->value))) {
            $route_parameters = [
              'entity_queue' => $subqueue->getQueue()->id(),
              'entity_subqueue' => $subqueue->id(),
            ];
            $view->element['#contextual_links']['entityqueue'] = [
              'route_parameters' => $route_parameters,
            ];
          }
        }
      }
    }
  }

  /**
   * Implements hook_contextual_links_view_alter().
   *
   * Change Entityqueue on views into off-canvas links if available.
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items) {
    if ($this->moduleHandler->moduleExists('settings_tray') && isset($element['#links']['entityentity-subqueueedit-form'])) {
      $element['#links']['entityentity-subqueueedit-form']['attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
        'data-settings-tray-edit' => TRUE,
      ];
    }
  }

  /**
   * Implements hook_entity_delete().
   *
   * @todo Remove this when https://www.drupal.org/node/2723323 is fixed.
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    // Get all the entity queues referencing the targets entity type.
    $queues = EntityQueue::loadMultipleByTargetType($entity->getEntityTypeId());
    foreach ($queues as $queue) {
      // Get all the subqueues which are referencing the deleted entity.
      $result = $this->entityTypeManager->getStorage('entity_subqueue')->getQuery()
        ->accessCheck(FALSE)
        ->condition('queue', $queue->id())
        ->condition('items', $entity->id())
        ->execute();
      $subqueues = EntitySubqueue::loadMultiple($result);

      // Check if the entity is referenced in a subqueue.
      foreach ($subqueues as $subqueue) {
        if ($subqueue->hasItem($entity)) {
          $subqueue->removeItem($entity)->save();
        }
      }
    }
  }

  /**
   * Implements hook_menu_local_tasks_alter().
   *
   * A queue's items management page differs by handler: queues with multiple
   * subqueues use a subqueue list, while simple queues land directly on their
   * single subqueue. Point the queue-level 'items' tab at whichever page the
   * queue's handler declares, and mirror the queue-level tabs ('Configure', the
   * Field UI tabs) onto a simple queue's subqueue page so it exposes the same
   * actions as the queue listing's operations.
   */
  #[Hook('menu_local_tasks_alter')]
  public function menuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface $cacheability): void {
    // A simple queue's items page is its single subqueue's canonical page.
    if ($route_name === 'entity.entity_subqueue.canonical') {
      $this->alterSubqueueItemsTabs($data, $cacheability);
      return;
    }

    // On the queue-level pages (Configure and the Field UI tabs), point the
    // 'items' tab at the page declared by the queue's handler.
    $queue = $this->routeMatch->getParameter('entity_queue');
    if ($queue instanceof EntityQueueInterface && isset($data['tabs'][0]['entity.entity_queue.subqueue_list']['#link'])) {
      $operation = $queue->getHandlerPlugin()->getItemsOperation();
      $link = &$data['tabs'][0]['entity.entity_queue.subqueue_list']['#link'];
      $link['title'] = $operation['title'];
      $link['url'] = $operation['url'];
      // The tab's target depends on the queue's handler configuration.
      $cacheability->addCacheableDependency($queue);
    }
  }

  /**
   * Adds the queue-level tabs to a simple queue's subqueue items page.
   *
   * @param array $data
   *   The local tasks render array, as passed to hook_menu_local_tasks_alter().
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheability metadata for the local tasks.
   */
  protected function alterSubqueueItemsTabs(array &$data, RefinableCacheableDependencyInterface $cacheability): void {
    $subqueue = $this->routeMatch->getParameter('entity_subqueue');
    if (!$subqueue instanceof EntitySubqueueInterface) {
      return;
    }

    $queue = $subqueue->getQueue();
    // The set of tabs depends on the queue's handler, so the result varies per
    // queue configuration.
    $cacheability->addCacheableDependency($queue);

    // Queues with multiple subqueues keep the regular per-subqueue tabs; their
    // queue-level tabs live on the subqueue list instead.
    $handler = $queue->getHandlerPlugin();
    if ($handler->supportsMultipleSubqueues()) {
      return;
    }

    // Relabel the edit tab to match the handler's items task and keep it first.
    if (isset($data['tabs'][0]['entity.entity_subqueue.canonical']['#link'])) {
      $data['tabs'][0]['entity.entity_subqueue.canonical']['#link']['title'] = $handler->getItemsOperation()['title'];
      $data['tabs'][0]['entity.entity_subqueue.canonical']['#weight'] = -10;
    }

    // Mirror the queue-level tabs anchored on the queue edit form. Pulling them
    // from the local task manager keeps them in sync with whatever Field UI and
    // other modules declare rather than hardcoding them here.
    $queue_tasks = $this->localTaskManager->getLocalTasks('entity.entity_queue.edit_form');
    $cacheability->addCacheableDependency($queue_tasks['cacheability']);
    foreach ($queue_tasks['tabs'] as $key => $tab) {
      // The items tab is already represented by this page's own tab, and none
      // of the queue-level tabs points at the current page.
      if ($key === 'entity.entity_queue.subqueue_list') {
        continue;
      }
      $tab['#active'] = FALSE;
      $data['tabs'][0][$key] = $tab;
    }
  }

}
