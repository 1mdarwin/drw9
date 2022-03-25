<?php

namespace Drupal\starrating\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'starrating' formatter.
 *
 * @FieldFormatter(
 *   id = "starrating_value",
 *   module = "starrating",
 *   label = @Translation("Star rating value"),
 *   field_types = {
 *     "starrating"
 *   }
 * )
 */
class StarRatingValueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'starrating_formatter',
        '#rate' => $item->value,
        '#type' => 'value',
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    $elements = [
      '#theme' => 'starrating_formatter',
      '#type' => 'value',
    ];
    $summary[] = $elements;

    return $summary;
  }

}
