<?php

declare(strict_types=1);

namespace Drupal\superfish\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * General hook implementations for Superfish.
 */
final class CoreHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?array {
    if ($route_name !== 'help.page.superfish') {
      return NULL;
    }

    return [
      '#theme' => 'superfish_help',
      '#title' => $this->t('About Superfish'),
      '#content' => $this->t('<a href="@url_module">Superfish module</a> integrates <a href="@url_library">jQuery Superfish</a> plugin to your Drupal menu blocks. Please refer to the module <a href="@url_documentation">documentation</a> for more information.', [
        '@url_library' => 'https://github.com/mehrpadin/Superfish-for-Drupal',
        '@url_module' => 'https://www.drupal.org/project/superfish',
        '@url_documentation' => 'https://www.drupal.org/node/1125896',
      ]),
    ];
  }

}
