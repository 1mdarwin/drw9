<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides base form for a entity instance configuration form.
 */
abstract class BlazyEntityFormBase extends EntityForm {

  /**
   * Defines the nice name.
   *
   * @var string
   */
  protected static $niceName = 'Slick';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'slick';

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $admin;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * The form elements.
   *
   * @var array
   */
  protected $formElements;

  /**
   * Returns the blazy admin service.
   */
  public function admin() {
    return $this->admin;
  }

  /**
   * Returns the blazy manager service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $admin_css = $this->manager->config('admin_css', 'blazy.settings');

    $form['#attributes']['class'][] = 'form--blazy form--slick form--optionset has-tooltip';
    $form['#attributes']['class'][] = 'form--' . self::$machineName;

    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate %name optionset</em>: @label', [
        '%name' => self::$niceName,
        '@label' => $this->entity->label(),
      ]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit %name optionset</em>: @label', [
        '%name' => self::$niceName,
        '@label' => $this->entity->label(),
      ]);
    }

    // Attach Slick admin library.
    $handler = $this->manager->moduleHandler();
    if ($admin_css) {
      if ($handler->moduleExists('slick_ui')) {
        $form['#attached']['library'][] = 'slick_ui/slick.admin.vtabs';
      }
      elseif ($handler->moduleExists('splide_ui')) {
        $form['#attached']['library'][] = 'splide_ui/admin.vtabs';
      }
    }

    return parent::form($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @todo revert #1497268, or use config_update instead.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $optionset = $this->entity;

    // Prevent leading and trailing spaces in slick names.
    $optionset->set('label', trim($optionset->label()));
    $optionset->set('id', $optionset->id());

    $status        = $optionset->save();
    $label         = $optionset->label();
    $edit_link     = $optionset->toLink($this->t('Edit'), 'edit-form')->toString();
    $config_prefix = $optionset->getEntityType()->getConfigPrefix();
    $message       = ['@config_prefix' => $config_prefix, '%label' => $label];

    $notice = [
      '@config_prefix' => $config_prefix,
      '%label' => $label,
      'link' => $edit_link,
    ];

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      $this->messenger()->addMessage($this->t('@config_prefix %label has been updated.', $message));
      $this->logger(self::$machineName)->notice('@config_prefix %label has been updated.', $notice);
    }
    else {
      // If we created a new entity.
      $this->messenger()->addMessage($this->t('@config_prefix %label has been added.', $message));
      $this->logger(self::$machineName)->notice('@config_prefix %label has been added.', $notice);
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return parent::save($form, $form_state);
  }

}
