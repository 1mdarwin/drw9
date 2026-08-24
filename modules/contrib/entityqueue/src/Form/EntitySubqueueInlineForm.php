<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Inline entity form handler for entity subqueues.
 *
 * Inline entity forms only build the entity's form display, so this handler
 * adds two things the display cannot provide on its own:
 * - The 'name' machine name element, since the 'name' base field has no widget.
 *   The standalone form controller adds it too; here an entity builder copies
 *   its value onto the entity because inline entity form, unlike
 *   ContentEntityForm, does not copy top-level form values to entity fields.
 * - The 'Reverse', 'Shuffle' and 'Clear' actions, which the standalone form
 *   gets from EntitySubqueueForm::actions().
 */
class EntitySubqueueInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    // The 'name' base field (the subqueue ID) has no form widget, so add the
    // same machine name element the standalone form uses and copy its value
    // onto the entity via an entity builder. When left empty,
    // EntitySubqueue::preSave() generates the machine name.
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $entity */
    $entity = $entity_form['#entity'];
    $entity_form['name'] = EntitySubqueueForm::buildMachineNameElement($entity, \Drupal::service('element_info'));
    $entity_form['#entity_builders'][] = [static::class, 'copyMachineName'];

    // Nothing more to act on if the items field is not part of the form.
    if (!isset($entity_form['items'])) {
      return $entity_form;
    }

    $ief_id = $entity_form['#ief_id'];
    $items_parents = array_merge($entity_form['#parents'], ['items']);

    $base_button = [
      '#type' => 'submit',
      '#submit' => [[static::class, 'reorderItemsSubmit']],
      // Only validate the items field, so the action works even while the
      // required title/name fields are still empty.
      '#limit_validation_errors' => [$items_parents],
      '#subqueue_field_parents' => $entity_form['#parents'],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $ief_id,
      ],
    ];

    $entity_form['subqueue_actions'] = [
      '#type' => 'actions',
      // Render the actions right after the items field.
      '#weight' => ($entity_form['items']['#weight'] ?? 0) + 0.5,
    ];
    $entity_form['subqueue_actions']['reverse'] = $base_button + [
      '#value' => $this->t('Reverse'),
      '#op' => 'reverse',
      '#name' => 'subqueue-reverse-' . $ief_id,
    ];
    $entity_form['subqueue_actions']['shuffle'] = $base_button + [
      '#value' => $this->t('Shuffle'),
      '#op' => 'shuffle',
      '#name' => 'subqueue-shuffle-' . $ief_id,
    ];
    $entity_form['subqueue_actions']['clear'] = $base_button + [
      '#value' => $this->t('Clear'),
      '#op' => 'clear',
      '#name' => 'subqueue-clear-' . $ief_id,
    ];

    return $entity_form;
  }

  /**
   * Entity builder: copies the submitted machine name onto a new subqueue.
   *
   * Inline entity form builds the entity purely from its form display and does
   * not copy top-level form values like the 'name' element. Only set it for new
   * subqueues; on an existing one the ID is immutable, and an empty value is
   * left for EntitySubqueue::preSave() to fill in.
   */
  public static function copyMachineName($entity_type_id, EntityInterface $entity, array &$entity_form, FormStateInterface $form_state) {
    if (!$entity->isNew()) {
      return;
    }

    $value = $form_state->getValue(array_merge($entity_form['#parents'], ['name']));
    if (is_string($value) && $value !== '') {
      $entity->set('name', $value);
    }
  }

  /**
   * Submit callback for the 'reverse', 'shuffle' and 'clear' actions.
   *
   * Inline entity forms rebuild the subqueue entity from scratch on every
   * request, so there is no persistent entity to reorder. Unlike the standalone
   * form, which calls the entity's own reorder methods, this reorders the items
   * widget's submitted rows directly: the form rebuild preserves that input and
   * the inline form save reads it back. Reordering the rows also keeps each
   * value in its widget's format, which rebuilding them from the entity would
   * not.
   */
  public static function reorderItemsSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];
    $field_parents = $trigger['#subqueue_field_parents'];
    $items_path = array_merge($field_parents, ['items']);

    $input = $form_state->getUserInput();
    $items_input = NestedArray::getValue($input, $items_path) ?: [];

    // Split the numeric value rows from control keys such as 'add_more'.
    $rows = [];
    $extra = [];
    foreach ($items_input as $key => $value) {
      if (is_int($key) || ctype_digit((string) $key)) {
        $rows[(int) $key] = $value;
      }
      else {
        $extra[$key] = $value;
      }
    }

    // Respect the current drag order before applying the operation.
    uasort($rows, fn($a, $b) => (int) ($a['_weight'] ?? 0) <=> (int) ($b['_weight'] ?? 0));
    $rows = array_values($rows);

    switch ($op) {
      case 'reverse':
        $rows = array_reverse($rows);
        break;

      case 'shuffle':
        shuffle($rows);
        break;

      case 'clear':
        $rows = [];
        break;
    }

    // Re-key sequentially and reset the weights to match the new order.
    $new_input = [];
    foreach ($rows as $delta => $row) {
      if (is_array($row)) {
        $row['_weight'] = $delta;
      }
      $new_input[$delta] = $row;
    }
    $new_input += $extra;
    NestedArray::setValue($input, $items_path, $new_input);
    $form_state->setUserInput($input);

    // Keep the widget's item count in sync so the rebuilt form renders the
    // correct number of rows.
    $field_state = WidgetBase::getWidgetState($field_parents, 'items', $form_state);
    $field_state['items_count'] = count($rows);
    WidgetBase::setWidgetState($field_parents, 'items', $form_state, $field_state);

    $form_state->setRebuild();
  }

}
