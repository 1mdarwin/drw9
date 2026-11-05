<?php

namespace Drupal\profile;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Provides a service to store profiles for saving.
 *
 * @internal
 */
final class ProfileUserSyncer {

  /**
   * Profiles queued for users.
   *
   * @var array
   */
  protected array $preparedProfiles = [];

  /**
   * Add a profile that should be saved once the user exists.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   */
  public function addPreparedProfile(UserInterface $user, ProfileInterface $profile): void {
    $uuid = $user->uuid();
    // The same profile can be handed over twice: once by the form widget and
    // once by the computed profile field when the account is saved.
    if (!in_array($profile, $this->preparedProfiles[$uuid] ?? [], TRUE)) {
      $this->preparedProfiles[$uuid][] = $profile;
    }
  }

  /**
   * Gets and resets prepared profiles for a user.
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *   The list of prepared profile.
   */
  public function flushPreparedProfiles(UserInterface $user): array {
    $uuid = $user->uuid();
    $profiles = $this->preparedProfiles[$uuid] ?? [];
    unset($this->preparedProfiles[$uuid]);
    return $profiles;
  }

  /**
   * Saves a profile immediately or queue if user is new.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   */
  public function saveProfile(UserInterface $user, ProfileInterface $profile): void {
    // For new users we don't have an ID yet to set an owner on the profile,
    // and thus we can't save the profile yet, but do this in
    // hook_user_insert instead.
    if ($user->isNew()) {
      $this->addPreparedProfile($user, $profile);
    }
    else {
      $profile->setOwnerId($user->id());
      $profile->setPublished();
      $profile->save();
    }
  }

}
