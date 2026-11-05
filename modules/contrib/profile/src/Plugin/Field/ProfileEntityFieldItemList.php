<?php

namespace Drupal\profile\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Utility\FiberResumeType;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Represents a profile entity reference field.
 */
class ProfileEntityFieldItemList extends FieldItemList implements EntityReferenceFieldItemListInterface {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    $this->ensureComputedValueInFiber();
    return parent::__get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($property_name) {
    $this->ensureComputedValueInFiber();
    return parent::__isset($property_name);
  }

  /**
   * Computes the list inside a private fiber when running inside a fiber.
   *
   * This is a workaround for a PHP bug where a fiber suspend from within a
   * magic method call can incorrectly trigger PHP's recursion guarding.
   * Computing this list loads the profiles through
   * EntityStorageBase::loadMultiple(), which suspends the current fiber before
   * core's item-level workaround in EntityReferenceItemBase::__get() is
   * reached. Computing the list in a private fiber that is driven to
   * completion means the magic methods never suspend. FiberResumeType only
   * exists since Drupal 11.3.
   *
   * @see https://github.com/php/php-src/issues/14983
   * @see https://www.drupal.org/project/drupal/issues/3565937
   */
  protected function ensureComputedValueInFiber(): void {
    if ($this->valueComputed || !\Fiber::getCurrent()) {
      return;
    }
    $fiber = new \Fiber(fn() => $this->ensureComputedValue());
    $fiber->start();
    while (!$fiber->isTerminated()) {
      if ($fiber->isSuspended()) {
        $resume_type = $fiber->resume();
        // @phpstan-ignore class.notFound, class.notFound
        if (!$fiber->isTerminated() && !($resume_type instanceof FiberResumeType && $resume_type === FiberResumeType::Immediate)) {
          usleep(500);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    foreach ($this->getUserProfiles() as $delta => $value) {
      $this->list[$delta] = $this->createItem($delta, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return $this->getUserProfiles();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $user = $this->getEntity();
    // While the account is new it has no ID, so saving a referenced profile
    // here would insert it with uid 0, and every hook_profile_insert()
    // implementation would resolve an anonymous owner. Hand those profiles to
    // the syncer instead: profile_user_insert() inserts them, correctly owned,
    // as soon as the account ID exists.
    if ($user->isNew()) {
      /** @var \Drupal\profile\ProfileUserSyncer $profile_user_syncer */
      $profile_user_syncer = \Drupal::service('profile.user_syncer');
      foreach ($this->list as $item) {
        if ($item->entity instanceof ProfileInterface && $item->entity->isNew()) {
          $profile_user_syncer->addPreparedProfile($user, $item->entity);
        }
      }
      return;
    }

    parent::preSave();
  }

  /**
   * Get the user's profiles.
   *
   * @return array|\Drupal\profile\Entity\ProfileInterface[]
   *   An array of profiles.
   */
  protected function getUserProfiles() {
    $user = $this->getEntity();
    assert($user instanceof UserInterface);
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

    // Ignore anonymous and user accounts not saved yet.
    if ($user->isAnonymous() || $user->isNew()) {
      return [];
    }

    $profiles = $profile_storage->loadMultipleByUser($user, $this->getSetting('profile_type'), TRUE);

    // This will renumber the keys while preserving the order of elements.
    $profiles = array_values($profiles);

    return $profiles;
  }

}
