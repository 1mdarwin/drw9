<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Plugin\views\EntityQueueHandlerTrait;
use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Filter handler for the position of an item in a queue.
 *
 * Filters by the item delta through the entity queue relationship join, so a
 * view can show, for example, only the first five items in a queue, or only
 * items from position six on.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_queue_position")
 */
class EntityQueuePosition extends NumericFilter {

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

    $entity_queue_relationship = $this->getEntityQueueRelationshipOrWarn($this->t('In order to filter on the item position in the queue, you need to add an <em>Entityqueue</em> relationship on the %display display of the %view view.', [
      '%view' => $this->view->storage->label(),
      '%display' => $this->view->current_display,
    ]));
    if (!$entity_queue_relationship) {
      return;
    }

    // Point the numeric operators at the delta column on the relationship join,
    // the same column the position field and sort read.
    $field = "{$entity_queue_relationship->firstAlias}.{$this->realField}";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
