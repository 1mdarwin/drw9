<?php

declare(strict_types=1);

namespace Drupal\superfish\Utility;

/**
 * Provides utility methods for Superfish.
 */
final class SuperfishUtility {

  /**
   * Recursively removes empty non-boolean values from an array.
   */
  public static function arrayFilter(array $values): array {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $values[$key] = self::arrayFilter($value);
      }
      elseif (empty($value) && !is_bool($value) && $value != '0') {
        unset($values[$key]);
      }
    }

    return $values;
  }

}
