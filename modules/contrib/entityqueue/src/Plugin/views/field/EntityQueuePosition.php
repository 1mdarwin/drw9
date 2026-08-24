<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Plugin\views\EntityQueueHandlerTrait;
use Drupal\views\Plugin\views\field\NumericField;

/**
 * Field handler to display the position of an item in a queue.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_queue_position")
 */
class EntityQueuePosition extends NumericField {

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

    $entity_queue_relationship = $this->getEntityQueueRelationshipOrWarn($this->t('In order to display the item position in the queue, you need to add an <em>Entityqueue</em> relationship on the %display display of the %view view.', [
      '%view' => $this->view->storage->label(),
      '%display' => $this->view->current_display,
    ]));
    if (!$entity_queue_relationship) {
      return;
    }

    $this->field_alias = $this->query->addField($entity_queue_relationship->firstAlias, $this->realField);
  }

}
