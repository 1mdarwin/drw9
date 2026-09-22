<?php

declare(strict_types=1);

namespace Drupal\superfish\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations related to Superfish rendering.
 */
final class ThemeHooks {

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_view_superfish_alter')]
  public function blockViewSuperfishAlter(array &$build, BlockPluginInterface $block): void {
    $build['#contextual_links']['menu'] = [
      'route_parameters' => ['menu' => $block->getDerivativeId()],
    ];
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'superfish' => [
        'render element' => 'element',
        'file' => 'superfish.theme.inc',
      ],
      'superfish_menu_items' => [
        'render element' => 'element',
        'file' => 'superfish.theme.inc',
      ],
      'superfish_help' => [
        'variables' => [
          'title' => NULL,
          'content' => NULL,
        ],
        'template' => 'superfish--help',
      ],
    ];
  }

}
