<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Plugin\views\EntityQueueHandlerTrait;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("entity_queue_position")
 */
class EntityQueuePosition extends SortPluginBase {

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

    $entity_queue_relationship = $this->getEntityQueueRelationshipOrWarn($this->t('In order to sort by the queue position, you need to add an <em>Entityqueue</em> relationship on the %display display of the %view view.', [
      '%view' => $this->view->storage->label(),
      '%display' => $this->view->current_display,
    ]));
    if (!$entity_queue_relationship) {
      return;
    }

    $subqueue_items_table_alias = $entity_queue_relationship->firstAlias;
    $this->query->addOrderBy($subqueue_items_table_alias, $this->realField, $this->options['order']);
  }

}
