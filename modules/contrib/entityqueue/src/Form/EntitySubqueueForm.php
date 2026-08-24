<?php

declare(strict_types=1);

namespace Drupal\entityqueue\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;
use Drupal\entityqueue\EntitySubqueueInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the entity subqueue edit forms.
 */
class EntitySubqueueForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\entityqueue\EntitySubqueueInterface
   */
  protected $entity;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('element_info')
    );
  }

  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ElementInfoManagerInterface $element_info) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $queue = $this->entity->getQueue();
    if ($description = $queue->getDescription()) {
      $form['description'] = [
        '#type' => 'item',
        '#title' => $this->t('Description'),
        '#plain_text' => $description,
      ];
    }

    $form = parent::form($form, $form_state);

    if ($this->operation === 'add') {
      $form['#title'] = $this->t('Add subqueue');
    }
    else {
      $form['#title'] = $this->t('Edit subqueue %label', ['%label' => $this->entity->label()]);
    }

    // Since the form has ajax buttons, the $wrapper_id will change each time
    // one of those buttons is clicked. Therefore the whole form has to be
    // replaced, otherwise the buttons will have the old $wrapper_id and will
    // only work on the first click.
    if ($form_state->has('subqueue_form_wrapper_id')) {
      $wrapper_id = $form_state->get('subqueue_form_wrapper_id');
    }
    else {
      $wrapper_id = Html::getUniqueId($this->getFormId() . '-wrapper');
    }

    $form_state->set('subqueue_form_wrapper_id', $wrapper_id);
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['name'] = static::buildMachineNameElement($this->entity, $this->elementInfo);

    return $form;
  }

  /**
   * Builds the machine name form element for the subqueue 'name' field.
   *
   * The 'name' base field is the subqueue ID and has no form widget, so both
   * the standalone form and the inline entity form add this element to let the
   * ID be set from the title. It is optional: when left empty,
   * EntitySubqueue::preSave() generates a machine name from the title.
   *
   * @todo Use the 'Machine name' field widget when
   *   https://www.drupal.org/node/2685749 is committed.
   */
  public static function buildMachineNameElement(EntitySubqueueInterface $entity, ElementInfoManagerInterface $element_info): array {
    $info = $element_info->getInfo('machine_name');
    return [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#source_field' => 'title',
      '#process' => array_merge([[static::class, 'processMachineNameSource']], $info['#process']),
      '#machine_name' => [
        'exists' => '\Drupal\entityqueue\Entity\EntitySubqueue::load',
      ],
      '#required' => FALSE,
      '#disabled' => !$entity->isNew(),
      '#weight' => -5,
      '#access' => !$entity->getQueue()->getHandlerPlugin()->hasAutomatedSubqueues(),
    ];
  }

  /**
   * Form API callback: Sets the 'source' property of a machine_name element.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function processMachineNameSource($element, FormStateInterface $form_state, $form) {
    // The source (title) field sits next to this element, so derive the field
    // parents by dropping the last key ('name') from this element's parents.
    // This keeps the source wiring correct whether the element is at the top of
    // the standalone form or nested inside an inline entity form.
    $field_parents = array_slice($element['#parents'], 0, -1);
    $source_field_state = WidgetBase::getWidgetState($field_parents, $element['#source_field'], $form_state);

    // Hide the field widget if the source field is not configured properly or
    // if it doesn't exist in the form.
    if (empty($element['#source_field']) || empty($source_field_state['array_parents'])) {
      $element['#access'] = FALSE;
    }
    else {
      $source_field_element = NestedArray::getValue($form_state->getCompleteForm(), $source_field_state['array_parents']);
      $element['#machine_name']['source'] = $source_field_element[0]['value']['#array_parents'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['reverse'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reverse'),
      '#submit' => ['::submitAction'],
      '#op' => 'reverse',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    $actions['shuffle'] = [
      '#type' => 'submit',
      '#value' => $this->t('Shuffle'),
      '#submit' => ['::submitAction'],
      '#op' => 'shuffle',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    $actions['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#submit' => ['::submitAction'],
      '#op' => 'clear',
      '#ajax' => [
        'callback' => '::subqueueActionAjaxForm',
        'wrapper' => $form_state->get('subqueue_form_wrapper_id'),
      ],
    ];

    return $actions;
  }

  /**
   * Submit callback for the 'reverse', 'shuffle' and 'clear' actions.
   */
  public static function submitAction(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    // Check if we have a form element for the 'items' field.
    $path = array_merge($form['#parents'], ['items']);
    $key_exists = NULL;
    NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Remove any user input for the 'items' element in order to allow the
      // values set below to be applied.
      $user_input = $form_state->getUserInput();
      NestedArray::setValue($user_input, $path, NULL);
      $form_state->setUserInput($user_input);

      $entity = $form_state->getFormObject()->getEntity();
      $items_widget = $form_state->getFormObject()->getFormDisplay($form_state)->getRenderer('items');

      $subqueue_items = $entity->get('items');
      $items_widget->extractFormValues($subqueue_items, $form, $form_state);

      switch ($op) {
        case 'reverse':
          $entity->reverseItems();
          break;

        case 'shuffle':
          $entity->shuffleItems();
          break;

        case 'clear':
          // Also reset the widget's item count so it renders no rows.
          $parents = NestedArray::getValue($form, $path)['widget']['#field_parents'];
          $field_state = WidgetBase::getWidgetState($parents, 'items', $form_state);
          $field_state['items_count'] = 0;
          WidgetBase::setWidgetState($parents, 'items', $form_state, $field_state);
          $entity->clearItems();
          break;
      }

      // Handle 'inline_entity_form' widgets separately because they have a
      // custom form state storage for the current state of the referenced
      // entities.
      if (\Drupal::moduleHandler()->moduleExists('inline_entity_form') && $items_widget instanceof InlineEntityFormBase) {
        $items_form_element = NestedArray::getValue($form, $path);
        $ief_id = $items_form_element['widget']['#ief_id'];

        $entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']);

        if (isset($entities)) {
          $form_state->set(['inline_entity_form', $ief_id, 'entities'], []);

          switch ($op) {
            case 'reverse':
              $entities = array_reverse($entities);
              break;

            case 'shuffle':
              shuffle($entities);
              break;

            case 'clear':
              $entities = [];
              break;
          }

          foreach ($entities as $delta => $item) {
            $item['weight'] = $delta;
            $form_state->set(['inline_entity_form', $ief_id, 'entities', $delta], $item);
          }
        }
      }

      // Handle 'entity_browser' widgets separately because they have a custom
      // form state storage for the current state of the referenced entities.
      if (\Drupal::moduleHandler()->moduleExists('entity_browser') && $items_widget instanceof EntityReferenceBrowserWidget) {
        $ids = array_column($subqueue_items->getValue(), 'target_id');
        $widget_id = $subqueue_items->getEntity()->uuid() . ':' . $subqueue_items->getFieldDefinition()->getName();
        $form_state->set(['entity_browser_widget', $widget_id], $ids);
      }

      $form_state->getFormObject()->setEntity($entity);

      $form_state->setRebuild();
    }
  }

  /**
   * AJAX callback; Returns the entire form element.
   */
  public static function subqueueActionAjaxForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $subqueue = $this->entity;
    $status = (int) $subqueue->save();

    $edit_link = $subqueue->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('The entity subqueue %label has been updated.', [
        '%label' => $subqueue->label(),
      ]));
      $this->logger('entityqueue')->notice('The entity subqueue %label has been updated.', [
        '%label' => $subqueue->label(),
        'link' => $edit_link,
      ]);
    }
    else {
      $this->messenger()->addMessage($this->t('The entity subqueue %label has been added.', [
        '%label' => $subqueue->label(),
      ]));
      $this->logger('entityqueue')->notice('The entity subqueue %label has been added.', [
        '%label' => $subqueue->label(),
        'link' => $edit_link,
      ]);
    }

    // Keep users on an accessible post-save page in order of preference.
    $account = $this->currentUser();
    $queue = $subqueue->getQueue();
    $redirect_candidates = [$subqueue->toUrl('edit-form')];
    if ($queue->getHandlerPlugin()->supportsMultipleSubqueues()) {
      $redirect_candidates[] = $queue->toUrl('subqueue-list');
    }
    $redirect_candidates[] = $queue->toUrl('collection');

    foreach ($redirect_candidates as $redirect_candidate) {
      if ($redirect_candidate->access($account)) {
        $form_state->setRedirectUrl($redirect_candidate);
        break;
      }
    }

    return $status;
  }

}
