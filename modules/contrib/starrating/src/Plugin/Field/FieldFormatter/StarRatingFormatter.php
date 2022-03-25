<?php

namespace Drupal\starrating\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Starrating' formatter.
 *
 * @FieldFormatter(
 *   id = "starrating",
 *   module = "starrating",
 *   label = @Translation("Star rating"),
 *   field_types = {
 *     "starrating"
 *   }
 * )
 */
class StarRatingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'fill_blank' => 0,
      'icon_type' => 'star',
      'icon_color' => 1,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $field_settings = $this->getFieldSettings();
    $max = $field_settings['max_value'];
    $min = 0;
    $icon_type = $this->getSetting('icon_type');
    $icon_color = $this->getSetting('icon_color');
    $fill_blank = $this->getSetting('fill_blank');
    foreach ($items as $delta => $item) {
      $rate = $item->value;
      $elements[$delta] = [
        '#theme' => 'starrating_formatter',
        '#rate' => $rate,
        '#min' => $min,
        '#max' => $max,
        '#icon_type' => $icon_type,
        '#icon_color' => $icon_color,
        '#fill_blank' => $fill_blank,
      ];
    }
    $elements['#attached']['library'][] = 'starrating/' . $icon_type;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $element = array();
    $element['fill_blank'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fill with blank icons'),
      '#default_value' => $this->getSetting('fill_blank'),
    );

    $element['icon_type'] = array(
      '#type' => 'select',
      '#title' => t('Icon type'),
      '#options' => array(
        'star' => t('Star'),
        'starline' => t('Star (outline)'),
        'check' => t('Check'),
        'heart' => t('Heart'),
        'dollar' => t('Dollar'),
        'smiley' => t('Smiley'),
        'food' => t('Food'),
        'coffee' => t('Coffee'),
        'movie' => t('Movie'),
        'music' => t('Music'),
        'human' => t('Human'),
        'thumbsup' => t('Thumbs Up'),
        'car' => t('Car'),
        'airplane' => t('Airplane'),
        'fire' => t('Fire'),
        'drupalicon' => t('Drupalicon'),
        'custom' => t('Custom'),
      ),
      '#default_value' => $this->getSetting('icon_type'),
      '#prefix' => '<img src="' . $base_url . '/' . drupal_get_path('module', 'starrating') . '/css/sample.png" />',
    );
    $element['icon_color'] = array(
      '#type' => 'select',
      '#title' => t('Icon color'),
      '#options' => [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
      ],
      '#default_value' => $this->getSetting('icon_color'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $field_settings = $this->getFieldSettings();
    $max = $field_settings['max_value'];
    $min = 0;
    $icon_type = $this->getSetting('icon_type');
    $icon_color = $this->getSetting('icon_color');
    $fill_blank = $this->getSetting('fill_blank');
    $elements = [
      '#theme' => 'starrating_formatter',
      '#min' => $min,
      '#max' => $max,
      '#icon_type' => $icon_type,
      '#icon_color' => $icon_color,
      '#fill_blank' => $fill_blank,
    ];
    $elements['#attached']['library'][] = 'starrating/' . $icon_type;
    $summary[] = $elements;

    return $summary;
  }

}
