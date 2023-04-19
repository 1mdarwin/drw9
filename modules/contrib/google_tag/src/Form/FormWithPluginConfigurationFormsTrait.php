<?php

declare(strict_types=1);

namespace Drupal\google_tag\Form;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a trait to make working with embedded plugin forms easier.
 */
trait FormWithPluginConfigurationFormsTrait {

  /**
   * The `gathered_contexts` is an undocumented implementation details.
   *
   * Required for working with plugins that are context aware.
   */
  protected static function setGatheredContexts(ContextRepositoryInterface $context_repository, FormStateInterface $form_state): void {
    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $context_repository->getAvailableContexts());
  }

  /**
   * Builds plugin configuration form.
   *
   * @param string $group
   *   Form group to which this form will be attached.
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   Plugin object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form render array for plugin configuration form.
   */
  protected static function buildPluginConfigurationForm(string $group, PluginFormInterface $plugin, FormStateInterface $form_state): array {
    $plugin_form = $plugin->buildConfigurationForm([], $form_state);
    $plugin_form['#type'] = 'details';
    if ($plugin instanceof PluginInspectionInterface) {
      $plugin_form['#title'] = $plugin->getPluginDefinition()['label'];
    }
    $plugin_form['#group'] = $group;
    return $plugin_form;
  }

  /**
   * Validates plugin configuration form.
   *
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   Plugin object.
   * @param array $subform
   *   Subform render array for plugin.
   * @param array $form
   *   Parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Parent form state.
   */
  protected static function validatePluginConfigurationForm(PluginFormInterface $plugin, array &$subform, array $form, FormStateInterface $form_state): void {
    $plugin->validateConfigurationForm(
      $subform,
      SubformState::createForSubform($subform, $form, $form_state)
    );
  }

  /**
   * Submits plugin configuration form.
   *
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   Plugin object.
   * @param array $subform
   *   Subform render array for plugin.
   * @param array $form
   *   Parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Parent form state.
   */
  protected static function submitPluginConfigurationForm(PluginFormInterface $plugin, array &$subform, array $form, FormStateInterface $form_state): void {
    $plugin->submitConfigurationForm(
      $subform,
      SubformState::createForSubform($subform, $form, $form_state)
    );
  }

}
