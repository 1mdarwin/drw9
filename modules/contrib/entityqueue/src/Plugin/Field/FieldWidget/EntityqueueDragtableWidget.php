<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\EntitySubqueueInterface;

/**
 * Plugin implementation of the 'entityqueue_dragtable' widget.
 *
 * @FieldWidget(
 *   id = "entityqueue_dragtable",
 *   label = @Translation("Autocomplete (draggable table)"),
 *   description = @Translation("An autocomplete text field with a draggable table."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityqueueDragtableWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_to_entity' => FALSE,
      'link_to_edit_form' => TRUE,
      'show_publish_status' => 'unpublished',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link label to the referenced entity'),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];
    $elements['link_to_edit_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a link to the edit form of the referenced entity'),
      '#default_value' => $this->getSetting('link_to_edit_form'),
    ];
    $elements['show_publish_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Show publication status'),
      '#options' => [
        'unpublished' => $this->t('Unpublished items only'),
        'all' => $this->t('All items'),
        'off' => $this->t('None'),
      ],
      '#default_value' => $this->getSetting('show_publish_status'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();
    if (!empty($settings['link_to_entity'])) {
      $summary[] = $this->t('Link to the referenced entity');
    }
    if (!empty($settings['link_to_edit_form'])) {
      $summary[] = $this->t('Link to the edit form of the referenced entity');
    }
    $status_summaries = [
      'unpublished' => $this->t('Publication status shown for unpublished items'),
      'all' => $this->t('Publication status shown for all items'),
    ];
    if (isset($status_summaries[$settings['show_publish_status']])) {
      $summary[] = $status_summaries[$settings['show_publish_status']];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    // Let core build the draggable table and per-row 'Remove' buttons.
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Drop core's empty trailing 'add' row and 'add another item' button in
    // favour of a dedicated 'add item' control, reusing core's ajax wrapper so
    // removing and adding refresh the same element.
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $wrapper_id = $elements['add_more']['#ajax']['wrapper'] ?? NULL;
    unset($elements['add_more'], $elements[$field_state['items_count']]);

    // Stop offering the 'add item' control once a subqueue is full; the widget
    // can also be used on ordinary entity reference fields, which have no max.
    $at_max = FALSE;
    $entity = $items->getEntity();
    if ($entity instanceof EntitySubqueueInterface) {
      $queue = $entity->getQueue();
      $max_size = $queue->getMaximumSize();
      $at_max = $max_size && !$queue->getActAsQueue() && $field_state['items_count'] >= $max_size;
    }

    if ($wrapper_id && !$at_max && !$form_state->isProgrammed()) {
      $id_prefix = implode('-', array_merge($form['#parents'], [$field_name]));
      $new_item = parent::formElement($items, -1, [], $form, $form_state);
      // Reject a non-existent reference up front rather than at save time.
      $new_item['target_id']['#validate_reference'] = TRUE;
      $elements['add_more'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => ['class' => ['form--inline']],
        '#attached' => ['library' => ['entityqueue/dragtable']],
        'new_item' => $new_item,
        'submit' => [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => $this->t('Add item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          // Validate only the new item, not the whole field, so adding to a
          // required field is not blocked by its empty NotNull constraint.
          '#limit_validation_errors' => [array_merge($form['#parents'], [$field_name, 'add_more', 'new_item'])],
          '#submit' => [[static::class, 'addItemSubmit']],
          '#ajax' => [
            'callback' => [static::class, 'addItemAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    assert($items instanceof EntityReferenceFieldItemListInterface);

    $field_name = $this->fieldDefinition->getName();

    // Prefer the submitted input on a rebuild (survives embedding in another
    // form), falling back to the field item list on the first build or a
    // reorder.
    $value_parents = array_merge($form['#parents'], [$field_name, $delta, 'target_id']);
    $value = NestedArray::getValue($form_state->getUserInput(), $value_parents);
    if (($value === NULL || $value === '') && isset($items[$delta]) && !$items[$delta]->isEmpty()) {
      $value = $items[$delta]->target_id;
    }

    // Only a saved reference renders as a read-only label. The empty trailing
    // row and not-yet-saved auto-created entries fall through to the parent's
    // autocomplete.
    $referenced_entity = ($value === NULL || $value === '')
      ? NULL
      : \Drupal::entityTypeManager()->getStorage($this->getFieldSetting('target_type'))->load($value);
    if (!$referenced_entity) {
      return parent::formElement($items, $delta, $element, $form, $form_state);
    }

    // Existing items are shown as a read-only label rather than an editable
    // autocomplete, with the reference carried in a hidden value.
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $entity = $entity_repository->getTranslationFromContext($referenced_entity);
    $entity_label = ($this->getSetting('link_to_entity') && !$entity->isNew()) ? $entity->toLink()->toString() : $entity->label();

    $element += [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline']],
      'target_id' => [
        '#type' => 'hidden',
        '#value' => $value,
      ],
      'label' => [
        '#type' => 'item',
        '#markup' => ($entity->access('view label')) ? $entity_label : $this->t('- Restricted access -'),
        '#weight' => 0,
      ],
    ];

    // Flag an item with its referenced entity's publication status. Only
    // entities implementing EntityPublishedInterface qualify, and the marker
    // follows the label's access check so a restricted item does not leak its
    // status.
    $status_display = $this->getSetting('show_publish_status');
    if ($status_display !== 'off' && $entity instanceof EntityPublishedInterface && $entity->access('view label')) {
      $published = $entity->isPublished();
      if (!$published || $status_display === 'all') {
        $status_class = $published ? 'entityqueue-item-status--published' : 'entityqueue-item-status--unpublished';
        $status_label = $published ? $this->t('Published') : $this->t('Unpublished');
        $element['status'] = [
          '#type' => 'item',
          '#markup' => '<span class="entityqueue-item-status ' . $status_class . '">' . $status_label . '</span>',
          '#attached' => ['library' => ['entityqueue/dragtable']],
        ];
      }
    }

    // Show a link to the edit form of the entity if the entity type is
    // editable.
    if ($this->getSetting('link_to_edit_form') && !$entity->isNew() && $entity->getEntityType()->hasLinkTemplate('edit-form')) {
      $element['_edit'] = $entity->toLink($this->t('Edit'), 'edit-form', ['query' => ['destination' => \Drupal::urlGenerator()->generateFromRoute('<current>')]])->toRenderable() + [
        '#attributes' => ['class' => ['form-item', 'entityqueue-edit-item-link']],
      ];
      $element['#attached']['html_head'][] = [
        [
          '#tag' => 'style',
          '#value' => '.js-form-wrapper .form-wrapper .form-item.entityqueue-edit-item-link { margin-left: 1em }',
        ],
        'entityqueue-edit-item-link',
      ];
    }

    return $element;
  }

  /**
   * Submission handler for the "Add item" button.
   */
  public static function addItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $add_more_parents = array_slice($button['#parents'], 0, -1);
    $submitted_values = NestedArray::getValue($form_state->getValues(), $add_more_parents);
    if (!empty($submitted_values['new_item']['target_id'])) {
      $target_id = $submitted_values['new_item']['target_id'];

      // An auto-created entity has no ID yet, so carry the typed label; the
      // parent autocomplete re-creates it on submit.
      $row_value = is_array($target_id) ? $target_id['entity']->label() : $target_id;

      // Append the new row to user input so it survives the rebuild, and bump
      // the widget's item count.
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      $delta = $field_state['items_count'];
      $field_state['items_count']++;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);

      $user_input = $form_state->getUserInput();
      $items_path = array_merge($parents, [$field_name]);
      $rows = NestedArray::getValue($user_input, $items_path) ?: [];
      $rows[$delta] = ['target_id' => $row_value, '_weight' => $delta];
      NestedArray::setValue($user_input, $items_path, $rows);

      // Clear the 'add item' autocomplete so it's blank for the next entry.
      NestedArray::setValue($user_input, array_merge($add_more_parents, ['new_item', 'target_id']), '');
      $form_state->setUserInput($user_input);
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add item" button.
   */
  public static function addItemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Render validation messages into the replaced wrapper; otherwise an
    // invalid entry only shows its error on the next interaction. Rendering
    // also clears them so they do not reappear later.
    $build = ['#type' => 'status_messages'];
    $messages = \Drupal::service('renderer')->renderRoot($build);
    $element['#prefix'] = ($element['#prefix'] ?? '') . $messages;

    return $element;
  }

}
