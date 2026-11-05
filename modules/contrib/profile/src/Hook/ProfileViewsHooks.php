<?php

namespace Drupal\profile\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hook implementations for Profile.
 */
class ProfileViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   *
   * Adds a relationship from the user table to its profile entity.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['users_field_data']['profile']['relationship'] = [
      'title' => $this->t('Profile'),
      'label' => $this->t('Profile'),
      'group' => 'User',
      'help' => $this->t('Reference to the profile of a user.'),
      'id' => 'standard',
      'base' => 'profile',
      'base field' => 'uid',
      'field' => 'uid',
    ];
    $data['users_field_data']['profile_type']['relationship'] = [
      'title' => $this->t('Profile Type'),
      'label' => $this->t('Profile Type'),
      'group' => 'User',
      'help' => $this->t('Reference to a specific profile type of a user.'),
      'id' => 'profile_relationship',
      'base' => 'profile',
      'base field' => 'uid',
      'field' => 'uid',
    ];
  }

}
