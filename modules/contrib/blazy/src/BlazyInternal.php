<?php

namespace Drupal\blazy;

use Drupal\blazy\Media\BlazyImage;
use Drupal\blazy\Utility\Check;
use Drupal\blazy\Utility\CheckItem;
use Drupal\blazy\Media\Placeholder;

/**
 * Provides internal non-reusable blazy utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class BlazyInternal {

  /**
   * Prepares the essential settings, URI, delta, etc.
   */
  public static function prepare(array &$settings, $item = NULL, $delta = -1): void {
    CheckItem::essentials($settings, $item, $delta);

    if ($settings['blazies']->get('image.uri')) {
      CheckItem::multimedia($settings);
      CheckItem::unstyled($settings);
      CheckItem::insanity($settings);
    }
  }

  /**
   * Blazy is prepared with an URI, provides few attributes as needed.
   */
  public static function prepared(array &$attributes, array &$settings, $item = NULL): void {
    // Prepare image URL and its dimensions, including for rich-media content,
    // such as for local video poster image if a poster URI is provided.
    BlazyImage::prepare($settings, $item);

    // Build thumbnail and optional placeholder based on thumbnail.
    Placeholder::prepare($attributes, $settings);
  }

  /**
   * Preserves crucial blazy specific settings to avoid accidental overrides.
   *
   * To pass the first found Blazy formatter cherry settings into the container,
   * like Blazy Grid which lacks of options like `Media switch` or lightboxes,
   * so that when this is called at the container level, it can populate
   * lightbox gallery attributes if so configured.
   * This way at Views style, the container can have lightbox galleries without
   * extra settings, as long as `Use field template` is disabled under
   * `Style settings`, otherwise flattened out as a string.
   *
   * @see \Drupa\blazy\BlazyManagerBase::isBlazy()
   */
  public static function preserve(array &$parentsets, array &$childsets): void {
    $cherries = BlazyDefault::cherrySettings();

    foreach ($cherries as $key => $value) {
      $fallback = $parentsets[$key] ?? $value;
      // Ensures to respect parent formatter or Views style if provided.
      $parentsets[$key] = isset($childsets[$key]) && empty($fallback)
        ? $childsets[$key]
        : $fallback;
    }

    $parent = $parentsets['blazies'] ?? NULL;
    $child = $childsets['blazies'] ?? NULL;
    if ($parent && $child) {
      // $parent->set('first.settings', array_filter($child));
      // $parent->set('first.item_id', $child->get('item.id'));
      // Hints containers to build relevant lightbox gallery attributes.
      $childbox = $child->get('lightbox.name');
      $parentbox = $parent->get('lightbox.name');

      // Ensures to respect parent formatter or Views style if provided.
      // The moral of this method is only if parent lacks of settings like Grid.
      if ($childbox && !$parentbox) {
        $optionset = $child->get('lightbox.optionset', $childbox);
        $parent->set('lightbox.name', $childbox)
          ->set($childbox, $optionset)
          ->set('is.lightbox', TRUE)
          ->set('switch', $child->get('switch'));
      }

      $parent->set('first', $child->get('first'), TRUE)
        ->set('was.preserve', TRUE);
    }
  }

  /**
   * Preliminary settings, normally at container/ global level.
   */
  public static function preSettings(array &$settings): void {
    Blazy::verify($settings);

    $blazies = $settings['blazies'];
    if ($blazies->was('initialized')) {
      return;
    }

    // Checks for basic features.
    Check::container($settings);

    // Checks for lightboxes.
    Check::lightboxes($settings);

    // Checks for grids.
    Check::grids($settings);

    // Checks for Image styles, excluding Responsive image.
    BlazyImage::styles($settings);

    // Checks for lazy.
    Check::lazyOrNot($settings);

    // Marks it processed.
    $blazies->set('was.initialized', TRUE);
  }

  /**
   * Modifies the common UI settings inherited down to each item.
   */
  public static function postSettings(array &$settings = []): void {
    // Failsafe, might be called directly at ::attach() outside the workflow.
    Blazy::verify($settings);

    $blazies = $settings['blazies'];
    if (!$blazies->was('initialized')) {
      self::preSettings($settings);
    }
  }

}
