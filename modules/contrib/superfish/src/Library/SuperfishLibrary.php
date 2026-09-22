<?php

declare(strict_types=1);

namespace Drupal\superfish\Library;

use Composer\Semver\VersionParser;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\BootstrapConfigStorageFactory;

/**
 * Provides discovery and version information for the Superfish library.
 */
final class SuperfishLibrary {

  /**
   * Returns the Superfish library folder location.
   */
  public static function path(string $library = 'drupal-superfish') {
    $directory = FALSE;
    // Ensure the Libraries API module is installed and working.
    if (\Drupal::hasService('library.libraries_directory_file_finder')) {
      $directory = \Drupal::service('library.libraries_directory_file_finder')->find($library);
      if ($directory) {
        return $directory;
      }
    }
    // Otherwise, use the default directory.
    if (\Drupal::hasContainer()) {
      $profile = \Drupal::installProfile();
    }
    else {
      $profile = BootstrapConfigStorageFactory::getDatabaseStorage()->read('core.extension')['profile'];
    }

    $profile_library_sub_path = $profile . '/libraries/' . $library;
    $paths = [
      'profiles/' . $profile_library_sub_path,
      'profiles/contrib/' . $profile_library_sub_path,
      'profiles/custom/' . $profile_library_sub_path,
      'libraries/' . $library,
      'sites/all/libraries/' . $library,
      'sites/default/libraries/' . $library,
    ];

    // Convert relative paths to absolute real paths and check existence.
    foreach ($paths as $relative_path) {
      $absolute_path = DRUPAL_ROOT . '/' . $relative_path;
      if (file_exists($absolute_path)) {
        $directory = $absolute_path;
        break;
      }
    }

    if (!$directory && Unicode::ucfirst($library) !== $library) {
      $directory = self::path(Unicode::ucfirst($library));
    }
    return $directory;
  }

  /**
   * Checks whether the Superfish library is installed.
   */
  public static function isInstalled(): bool {
    $directory = self::path();
    return $directory && file_exists($directory . '/superfish.js');
  }

  /**
   * Returns the installed Superfish library version.
   *
   * @return string
   *   A valid semver string, or an empty string if an error occurred.
   */
  public static function version(): string {
    try {
      if (($directory = self::path()) && file_exists($directory . '/VERSION')) {
        $version = file_get_contents($directory . '/VERSION');
        $parser = new VersionParser();
        return $parser->normalize($version);
      }
    }
    catch (\Exception $e) {
    }

    return '';
  }

}
