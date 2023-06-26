<?php

namespace Drupal\blazy;

/**
 * Provides common entity utilities to work with field details.
 *
 * This is alternative to Drupal\blazy\BlazyFormatter used outside
 * field managers, such as Views field, or Slick/Entity Browser displays, etc.
 *
 * @see Drupal\blazy\Field\BlazyEntityReferenceBase
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase
 */
interface BlazyEntityInterface {

  /**
   * Returns the blazy oembed service.
   *
   * @return \Drupal\blazy\Media\BlazyOEmbedInterface
   *   The blazy oembed.
   */
  public function oembed();

  /**
   * Returns the blazy manager service.
   *
   * @return \Drupal\blazy\BlazyManagerInterface
   *   The blazy manager.
   */
  public function blazyManager();

  /**
   * Build image/video preview either using theme_blazy(), or view builder.
   *
   * @param array $data
   *   An array of data containing settings, image item, entity, and fallback.
   * @param object $entity
   *   The deprecated media entity, else file entity to be associated to media.
   * @param string $fallback
   *   The deprecated fallback string such as file name or entity label.
   *
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty array.
   */
  public function build(array &$data, $entity = NULL, $fallback = ''): array;

  /**
   * Returns the entity view, if available.
   *
   * @param object $entity
   *   The entity being rendered.
   * @param array $settings
   *   The settings containing view_mode.
   * @param string $fallback
   *   The fallback content when all fails, probably just entity label.
   *
   * @return array
   *   The renderable array of the view builder, or empty if not applicable.
   */
  public function view($entity, array $settings = [], $fallback = ''): array;

}
