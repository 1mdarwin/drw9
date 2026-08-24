<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Plugin\views\EntityQueueHandlerTrait;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter for entities that are available or not in an entity queue.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_queue_in_queue")
 */
class EntityQueueInQueue extends BooleanOperator {

  use EntityQueueHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $this->defineEntityQueueOptions($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->buildEntityQueueOptionsForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $entity_queue_relationship = $this->getEntityQueueRelationshipOrWarn($this->t('In order to filter on items from the queue, you need to add an <em>Entityqueue</em> relationship on the %display display of the %view view.', [
      '%view' => $this->view->storage->label(),
      '%display' => $this->view->current_display,
    ]));
    if (!$entity_queue_relationship) {
      return;
    }

    $subqueue_items_table_alias = $entity_queue_relationship->firstAlias;
    $field_field = $this->definition['field field'];
    $operator = $this->value ? 'IS NOT NULL' : 'IS NULL';
    $condition = "$subqueue_items_table_alias.$field_field $operator";

    $this->query->addWhereExpression($this->options['group'], $condition);
  }

}
