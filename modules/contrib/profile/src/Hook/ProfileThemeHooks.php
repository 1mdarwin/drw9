<?php

namespace Drupal\profile\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hook implementations for Profile.
 */
class ProfileThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'profile' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessProfile',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK() for 'profile'.
   */
  #[Hook('theme_suggestions_profile')]
  public function themeSuggestionsProfile(array $variables): array {
    $original = $variables['theme_hook_original'];
    $entity = $variables['elements']['#profile'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

    $suggestions = [];
    $suggestions[] = $original;
    $suggestions[] = $original . '__' . $sanitized_view_mode;
    $suggestions[] = $original . '__' . $entity->bundle();
    $suggestions[] = $original . '__' . $entity->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = $original . '__' . $entity->id();
    $suggestions[] = $original . '__' . $entity->id() . '__' . $sanitized_view_mode;

    return $suggestions;
  }

  /**
   * Prepares variables for profile templates.
   *
   * Default template: profile.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing rendered fields.
   *   - attributes: HTML attributes for the containing element.
   */
  public function preprocessProfile(array &$variables): void {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $variables['elements']['#profile'];

    $variables['view_mode'] = $variables['elements']['#view_mode'];
    $variables['profile'] = $profile;
    $variables['url'] = $profile->id() ? $profile->toUrl() : FALSE;
    // Helpful $content variable for templates.
    $variables['content'] = [];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

}
