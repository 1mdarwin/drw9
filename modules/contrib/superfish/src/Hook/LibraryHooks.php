<?php

declare(strict_types=1);

namespace Drupal\superfish\Hook;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\superfish\Library\SuperfishLibrary;

/**
 * Hook implementations related to the Superfish library.
 */
final class LibraryHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_libraries_info().
   */
  #[Hook('libraries_info')]
  public function librariesInfo(): array {
    return [
      'superfish' => [
        'name' => 'superfish',
        'vendor url' => 'https://github.com/lobsterr/drupal-superfish',
        'download url' => 'https://github.com/lobsterr/drupal-superfish/zipball/2.x',
        'version callback' => [SuperfishLibrary::class, 'version'],
        'files' => [
          'js' => [
            'superfish.js',
            'jquery.hoverIntent.minified.js',
            'sfsmallscreen.js',
            'sftouchscreen.js',
            'supersubs.js',
            'supposition.js',
          ],
          'css' => [
            'css/superfish.css',
            'style/black/black.css',
            'style/blue/blue.css',
            'style/coffee/coffee.css',
            'style/default/default.css',
            'style/white/white.css',
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    $libraries = [];
    $superfish_library_path = SuperfishLibrary::path();
    if (!$superfish_library_path) {
      return $libraries;
    }

    $superfish_library_path = '/' . $superfish_library_path;
    $libraries = [
      'superfish' => [
        'remote' => 'https://github.com/lobsterr/drupal-superfish',
        'version' => '2.0',
        'license' => [
          'name' => 'MIT',
          'url' => 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html',
          'gpl-compatible' => FALSE,
        ],
        'js' => ['js/superfish.js' => []],
        'dependencies' => [
          'core/jquery',
          'core/drupal',
          'core/drupalSettings',
          'core/once',
        ],
      ],
      'init' => [
        'js' => [$superfish_library_path . '/superfish.js' => []],
        'css' => [
          'base' => [$superfish_library_path . '/css/superfish.css' => []],
        ],
      ],
    ];

    $plugins = [
      'hoverintent' => 'jquery.hoverIntent.minified.js',
      'smallscreen' => 'sfsmallscreen.js',
      'touchscreen' => 'sftouchscreen.js',
      'supersubs' => 'supersubs.js',
      'supposition' => 'supposition.js',
    ];
    foreach ($plugins as $plugin => $filename) {
      $options = $plugin === 'hoverintent' ? ['minified' => TRUE] : [];
      $libraries['superfish_' . $plugin] = [
        'js' => [$superfish_library_path . '/' . $filename => $options],
        'dependencies' => ['superfish/init'],
      ];
    }

    $styles = [
      'black',
      'blue',
      'coffee',
      'default',
      'white',
    ];

    foreach ($styles as $style) {
      $libraries['superfish_style_' . $style] = [
        'css' => [
          'theme' => [$superfish_library_path . '/style/' . $style . '/' . $style . '.css' => []],
        ],
        'dependencies' => ['superfish/init'],
      ];
    }

    if ($easing_library_path = SuperfishLibrary::path('easing')) {
      $libraries['superfish_easing'] = [
        'js' => ['/' . $easing_library_path . '/jquery.easing.js' => []],
      ];
    }

    return $libraries;
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    $requirements['superfish']['title'] = $this->t('Superfish library');

    $severity_ok = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.2.0', fn() => RequirementSeverity::OK, fn() => REQUIREMENT_OK);
    $severity_error = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.2.0', fn() => RequirementSeverity::Error, fn() => REQUIREMENT_ERROR);

    if (!SuperfishLibrary::isInstalled()) {
      $requirements['superfish']['value'] = $this->t('Not installed');
      $requirements['superfish']['severity'] = $severity_error;
      $requirements['superfish']['description'] = $this->t('Please download the Superfish library from :url.', [':url' => 'https://www.drupal.org/project/superfish']);
      return $requirements;
    }

    $version = SuperfishLibrary::version();
    if (!$version) {
      $requirements['superfish']['value'] = $this->t('Inaccessible');
      $requirements['superfish']['severity'] = $severity_error;
      $requirements['superfish']['description'] = $this->t('Cannot access the Superfish library directory; perhaps because its permissions and/or ownership are not set up correctly.');
      return $requirements;
    }

    $version_parts = explode('.', $version);
    if (empty($version_parts[0]) || !is_numeric($version_parts[0])) {
      $requirements['superfish']['value'] = $this->t('Unknown version');
      $requirements['superfish']['severity'] = $severity_error;
      $requirements['superfish']['description'] = $this->t('Cannot determine the version of your Superfish library.');
      return $requirements;
    }

    if (version_compare($version_parts[0], '2', '<')) {
      $requirements['superfish']['value'] = $this->t('Not supported');
      $requirements['superfish']['severity'] = $severity_error;
      $requirements['superfish']['description'] = $this->t('The Superfish library requires an update. You can find the update instructions on :url.', [':url' => 'https://www.drupal.org/project/superfish']);
      return $requirements;
    }

    $requirements['superfish']['value'] = $this->t('Installed; at @location', ['@location' => SuperfishLibrary::path()]);
    $requirements['superfish']['severity'] = $severity_ok;
    return $requirements;
  }

}
