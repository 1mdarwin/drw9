<?php

namespace Drupal\blazy\Form;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyAdminInterface {

  /**
   * Returns the entity display repository.
   */
  public function getEntityDisplayRepository();

  /**
   * Returns the typed config.
   */
  public function getTypedConfig();

  /**
   * Returns the blazy manager.
   */
  public function blazyManager();

  /**
   * Returns shared form elements across field formatter and Views.
   */
  public function openingForm(array &$form, array &$definition): void;

  /**
   * Returns re-usable grid elements across field formatter and Views.
   */
  public function gridForm(array &$form, array $definition): void;

  /**
   * Returns shared ending form elements across field formatter and Views.
   */
  public function closingForm(array &$form, array $definition): void;

  /**
   * Returns simple form elements common for Views field, EB widget, formatters.
   */
  public function baseForm(array $definition = []): array;

  /**
   * Returns re-usable media switch form elements.
   */
  public function mediaSwitchForm(array &$form, array $definition): void;

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, array $definition): void;

  /**
   * Returns time in interval for select options.
   */
  public function getCacheOptions(): array;

  /**
   * Returns available lightbox captions for select options.
   */
  public function getLightboxCaptionOptions(): array;

  /**
   * Returns available entities for select options.
   */
  public function getEntityAsOptions($entity_type = ''): array;

  /**
   * Returns available optionsets for select options.
   */
  public function getOptionsetOptions($entity_type = ''): array;

  /**
   * Returns available view modes for select options.
   */
  public function getViewModeOptions($target_type): array;

  /**
   * Returns Responsive image for select options.
   */
  public function getResponsiveImageOptions(): array;

  /**
   * Returns re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, array $definition): void;

  /**
   * Modifies the image formatter form elements.
   */
  public function imageStyleForm(array &$form, array $definition): void;

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary(array $definition): array;

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = ''
  ): array;

}
