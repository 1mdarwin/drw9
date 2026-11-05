<?php

namespace Drupal\profile\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\profile\Plugin\Field\ProfileEntityFieldItemList;
use Drupal\profile\ProfileUserSyncer;
use Drupal\user\UserInterface;

/**
 * Hook implementations for Profile.
 */
class ProfileHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ProfileHooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   * @param \Drupal\profile\ProfileUserSyncer $profileUserSyncer
   *   The profile user syncer.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected ProfileUserSyncer $profileUserSyncer,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.profile':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Profile module provides a fieldable entity, that allows administrators to define different sets of fields for user profiles, which are then displayed in the <a href="@user">My Account</a> section. This permits users of a site to share more information about themselves, and can help community-based sites organize users around specific information.', ['@user' => Url::fromRoute('user.page')->toString()]) . '</p>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Types of profiles') . '</dt>';
        $output .= '<dd>' . $this->t('Profile types provide a way of grouping similar data for user profiles e.g. Personal information, Work etc. A default "Personal information type is provided. You may create more types and manage fields for each type from the <a href="@profile-types">Profile types</a> admin page. When creating a new profile type, you will be able to specify whether a user may create multiple profiles or make the profile form available when registering a new user.', ['@profile-types' => Url::fromRoute('entity.profile_type.collection')->toString()]) . '</dd>';
        $output .= '<dt>' . $this->t('Creating profiles') . '</dt>';
        $output .= '<dd>' . $this->t('A user will see tabs they have access to, when editing their main user account e.g. "Add personal information profile". The visibility of a tab depends on whether they can create multiple profiles or if they haven\'t created a profile of the type that doesn\'t allow multiple instances.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(array &$bundles): void {
    if (empty($bundles['profile'])) {
      return;
    }

    $profile_type_ids = array_keys($bundles['profile']);
    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = $this->entityTypeManager->getStorage('profile_type')->loadMultiple($profile_type_ids);
    foreach ($bundles['profile'] as $bundle => $info) {
      if (isset($profile_types[$bundle])) {
        $profile_type = $profile_types[$bundle];
        // Bundle info is loaded on most requests. Store the flags inside, so
        // that modules can use them without needing to load the profile type.
        if ($profile_type->allowsMultiple()) {
          $bundles['profile'][$bundle]['multiple'] = TRUE;
        }
        if ($profile_type->showRevisionUi()) {
          $bundles['profile'][$bundle]['revision_ui'] = TRUE;
        }
      }
    }
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    if ($operation == 'view' && $items && $field_definition->getTargetEntityTypeId() == 'profile') {
      if ($field_definition instanceof FieldConfigInterface) {
        $is_private = $field_definition->getThirdPartySetting('profile', 'profile_private', FALSE);
        if ($is_private) {
          // Users may see their own private profile fields by default, so this
          // requires user granularity for caching.
          /** @var \Drupal\profile\Entity\ProfileInterface $profile */
          $profile = $items->getEntity();
          if ($account->id() === $profile->getOwnerId()) {
            return AccessResult::neutral()->addCacheContexts(['session']);
          }

          return AccessResult::forbiddenIf(!$account->hasPermission('administer profile'));
        }
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_jsonapi_entity_field_filter_access().
   */
  #[Hook('jsonapi_entity_field_filter_access')]
  public function jsonapiEntityFieldFilterAccess(FieldDefinitionInterface $field_definition, AccountInterface $account): AccessResultInterface {
    if ($field_definition->getTargetEntityTypeId() == 'profile') {
      if ($field_definition instanceof FieldConfigInterface) {
        $is_private = $field_definition->getThirdPartySetting('profile', 'profile_private', FALSE);
        if ($is_private) {
          return AccessResult::forbiddenIf(!$account->hasPermission('administer profile'));
        }
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_user_insert().
   */
  #[Hook('user_insert', order: Order::First)]
  public function userInsert(UserInterface $user): void {
    // Save any profiles that were prepared for the user.
    $profiles = $this->profileUserSyncer->flushPreparedProfiles($user);
    foreach ($profiles as $profile) {
      $profile->setOwnerId($user->id());
      $profile->setPublished();
      $profile->save();
    }
  }

  /**
   * Implements hook_user_delete().
   */
  #[Hook('user_delete')]
  public function userDelete(EntityInterface $entity): void {
    $list = $this->entityTypeManager
      ->getStorage('profile')
      ->loadByProperties([
        'uid' => $entity->id(),
      ]);

    foreach ($list as $profile) {
      $profile->delete();
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'field_config_edit_form'.
   */
  #[Hook('form_field_config_edit_form_alter')]
  public function formFieldConfigEditFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $field = $form_state->getFormObject()->getEntity();
    if ($field->getTargetEntityTypeId() != 'profile') {
      return;
    }

    $form['field']['profile']['profile_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a private field.'),
      '#default_value' => $field->getThirdPartySetting('profile', 'profile_private', FALSE),
    ];
    $form['actions']['submit']['#submit'][] = [static::class, 'fieldConfigEditFormSubmit'];
  }

  /**
   * Form submission handler for the field config edit form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function fieldConfigEditFormSubmit(array $form, FormStateInterface $form_state): void {
    $field = $form_state->getFormObject()->getEntity();
    $form_fields = &$form_state->getValues();

    // If the private option is checked, update settings.
    if ($form_fields['profile_private']) {
      $field->setThirdPartySetting('profile', 'profile_private', TRUE);
      $field->save();
    }
    else {
      $field->unsetThirdPartySetting('profile', 'profile_private');
      $field->save();
    }
  }

  /**
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter')]
  public function entityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    if ($context['entity_type'] !== 'profile') {
      return;
    }
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('profile');
    if (empty($bundle_info[$context['bundle']]['revision_ui'])) {
      $form_display->removeComponent('revision_log_message');
    }
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    $fields = [];
    if ($entity_type->id() !== 'user') {
      return $fields;
    }

    // In random cases, this hook is invoked before the profile_type entity
    // definition is registered.
    if (!$this->entityTypeManager->hasDefinition('profile_type')) {
      return $fields;
    }

    /** @var \Drupal\profile\Entity\ProfileType[] $profile_types */
    $profile_types = $this->entityTypeManager->getStorage('profile_type')->loadMultiple();
    foreach ($profile_types as $profile_type) {
      $profile_type_id = $profile_type->id();
      $fields[$profile_type_id . '_profiles'] = BaseFieldDefinition::create('entity_reference')
        ->setName(sprintf('%s profiles', $profile_type->label()))
        ->setLabel($this->t('@label profiles', ['@label' => $profile_type->label()]))
        ->setDescription($this->t('User profiles.'))
        ->setClass(ProfileEntityFieldItemList::class)
        ->setSetting('target_type', 'profile')
        ->setSetting('handler_settings', [
          'target_bundles' => [
            $profile_type_id => $profile_type_id,
          ],
        ])
        ->setSetting('profile_type', $profile_type_id)
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setCardinality($profile_type->allowsMultiple() ? BaseFieldDefinition::CARDINALITY_UNLIMITED : 1)
        ->setComputed(TRUE);
    }
    return $fields;
  }

  /**
   * Implements hook_migration_plugins_alter().
   */
  #[Hook('migration_plugins_alter')]
  public function migrationPluginsAlter(array &$migrations): void {
    foreach ($migrations as &$migration) {
      foreach (['entity_type', 'targetEntityType'] as $entity_type) {
        if (isset($migration['process'][$entity_type])) {
          $this->migrationEntityTypeAdjust($migration, $entity_type);
          $migration['migration_dependencies']['optional']['d7_profile2_type'] = 'd7_profile2_type';
        }
      }
    }
  }

  /**
   * Map profile2 fields to 'profile'.
   *
   * @param array $migration
   *   The migration to process.
   * @param string $destination
   *   The process destination.
   */
  protected function migrationEntityTypeAdjust(array &$migration, string $destination): void {
    if (!$this->migrationPrepareProcess($migration['process'], $destination)) {
      return;
    }

    $entity_type_process = &$migration['process'][$destination];
    $entity_type_process['profile'] = [
      'plugin' => 'static_map',
      'map' => [
        'profile2' => 'profile',
      ],
      'bypass' => TRUE,
    ];
  }

  /**
   * Prepare process array for adding another process elements.
   *
   * @param mixed $process
   *   The process array.
   * @param string $index
   *   The process index.
   *
   * @return bool
   *   Whether the processing is successful.
   *
   * @throws \Exception
   */
  protected function migrationPrepareProcess(&$process, string $index): bool {
    if (!isset($process[$index])) {
      return FALSE;
    }

    $p = &$process[$index];

    // Try to play with other modules altering this, and don't replace it
    // outright unless it's unchanged.
    if (is_string($p)) {
      $p = [
        [
          'plugin' => 'get',
          'source' => $p,
        ],
      ];
    }
    elseif (isset($p['plugin'])) {
      $p = [$p];
    }
    elseif (!is_array($p)) {
      throw new \Exception(sprintf('Unknown process element type: %s.', print_r($p, TRUE)));
    }

    return TRUE;
  }

}
