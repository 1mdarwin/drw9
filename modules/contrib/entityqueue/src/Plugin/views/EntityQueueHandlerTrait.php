<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship;

/**
 * Shared code for the Views handlers that work off an entity queue.
 */
trait EntityQueueHandlerTrait {

  /**
   * Adds the 'queue_relationship' option. Call this from defineOptions().
   *
   * @param array $options
   *   The options array to add to.
   */
  protected function defineEntityQueueOptions(array &$options): void {
    $options['queue_relationship'] = ['default' => NULL];
  }

  /**
   * Adds the relationship selector. Call from the handler's buildOptionsForm().
   *
   * Views hides the standard relationship dropdown for these handlers, so this
   * selector is how the user points a handler at one entity queue relationship
   * when the view has several. Each option is one relationship, labeled by the
   * queue (or queues) it is limited to. A handler uses a single relationship's
   * join, so it works at the relationship level: a relationship that covers
   * several queues filters/sorts on that whole set.
   *
   * @param array $form
   *   The options form to add to.
   */
  protected function buildEntityQueueOptionsForm(array &$form): void {
    // Read relationships from the stored display config rather than the
    // initialized handler objects: the Views UI builds this form without
    // initializing the other handlers, so $this->view->relationship is empty
    // here.
    $options = [];
    $relationships = $this->displayHandler->getOption('relationships') ?? [];
    foreach ($relationships as $relationship) {
      if (($relationship['plugin_id'] ?? NULL) === 'entity_queue') {
        $options[$relationship['id']] = $this->entityQueueRelationshipLabel($relationship);
      }
    }

    // Nothing to disambiguate with zero or one relationship: zero can't work at
    // all, and one is what the fallback already picks.
    if (count($options) < 2) {
      return;
    }

    $form['queue_relationship'] = [
      '#type' => 'select',
      '#title' => $this->t('Limit to a specific entity queue relationship'),
      '#description' => $this->t('Pick the entity queue relationship this targets when the view has more than one. Leave empty to use the first one.'),
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => $this->options['queue_relationship'],
    ];
  }

  /**
   * Builds the selector label for a relationship.
   *
   * Prefers the relationship's admin label, like the native Views relationship
   * selector. Falls back to the queue set so relationships left at the default
   * label stay distinguishable, the default is the same for all of them.
   *
   * @param array $relationship
   *   A relationship handler config array.
   *
   * @return string
   *   The label to show in the selector.
   */
  protected function entityQueueRelationshipLabel(array $relationship): string {
    if (!empty($relationship['admin_label'])) {
      return $relationship['admin_label'];
    }

    $queue_ids = array_filter((array) ($relationship['limit_queue'] ?? []));
    $labels = [];
    foreach (EntityQueue::loadMultiple($queue_ids) as $queue) {
      $labels[] = $queue->label();
    }

    return $labels ? implode(', ', $labels) : $relationship['id'];
  }

  /**
   * Finds the entity queue relationship for this handler.
   *
   * Resolution order:
   * 1. An explicitly configured Views relationship, if any. Views hides the
   *    relationship dropdown for these handlers, so this only happens for
   *    config written or exported by hand.
   * 2. The relationship picked in 'queue_relationship'. This is the UI path
   *    that lets each handler target its own relationship when the view has
   *    several.
   * 3. The first entity queue relationship in the view, so single-relationship
   *    views keep working without extra setup.
   *
   * @return \Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship|null
   *   The relationship handler, or NULL if the view has none.
   */
  protected function getEntityQueueRelationship(): ?EntityQueueRelationship {
    foreach ([$this->options['relationship'] ?? 'none', $this->options['queue_relationship'] ?? ''] as $id) {
      if ($id && $id !== 'none'
        && isset($this->view->relationship[$id])
        && $this->view->relationship[$id] instanceof EntityQueueRelationship) {
        return $this->view->relationship[$id];
      }
    }

    foreach ($this->view->relationship as $relationship) {
      if ($relationship instanceof EntityQueueRelationship) {
        return $relationship;
      }
    }

    return NULL;
  }

  /**
   * Resolves the relationship, warning admins when the view has none.
   *
   * @param string|\Stringable $warning
   *   The message to show to users who can administer views.
   *
   * @return \Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship|null
   *   The relationship handler, or NULL if the view has none.
   */
  protected function getEntityQueueRelationshipOrWarn(string|\Stringable $warning): ?EntityQueueRelationship {
    $relationship = $this->getEntityQueueRelationship();
    if (!$relationship && \Drupal::currentUser()->hasPermission('administer views')) {
      $this->messenger()->addMessage($warning, MessengerInterface::TYPE_ERROR);
    }

    return $relationship;
  }

}
