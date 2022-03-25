<?php

namespace Drupal\starrating\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'starrating' field type.
 *
 * @FieldType(
 *   id = "starrating",
 *   label = @Translation("Star rating"),
 *   module = "starrating",
 *   description = @Translation("Provides star rating for entities"),
 *   default_widget = "starrating",
 *   default_formatter = "starrating",
 * )
 */
class StarRatingItem extends FieldItemBase {

  const DEFAULT_MAX_RATING_VALUE = 10;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      // Declare a single setting, 'size', with a default
      // value of 'large'.
      'max_value' => self::DEFAULT_MAX_RATING_VALUE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Star rating'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = [];
    // The key of the element should be the setting name.
    $element['max_value'] = [
      '#title' => $this->t('Maximum rating value'),
      '#type' => 'select',
      '#options' => [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10',
      ],
      '#default_value' => $this->getSetting('max_value'),
    ];

    return $element;
  }

}
