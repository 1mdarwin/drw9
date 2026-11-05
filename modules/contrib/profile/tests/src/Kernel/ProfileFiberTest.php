<?php

namespace Drupal\Tests\profile\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Tests reading the computed profile fields from within a fiber.
 *
 * @group profile
 */
class ProfileFiberTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'profile',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('view');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['profile', 'user']);
  }

  /**
   * Tests fiber suspension within ProfileEntityFieldItemList::__get().
   *
   * @see https://github.com/php/php-src/issues/14983
   */
  public function testProfileListFiberSuspension(): void {
    $profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $profile_type->save();

    $user = $this->createUser();
    $profile = Profile::create([
      'type' => 'test',
      'uid' => $user->id(),
    ]);
    $profile->save();

    // Reload the user with an empty static cache so that reading the computed
    // list has to load the profile, which is where the fiber suspends.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('profile')->resetCache();
    $entity_type_manager->getStorage('user')->resetCache();
    $user = $entity_type_manager->getStorage('user')->load($user->id());

    $fiber = new \Fiber(fn() => $user->test_profiles->entity);
    $fiber->start();
    $loaded_profile = $user->test_profiles->entity;
    $this->assertIsObject($loaded_profile);
    $this->assertEquals($profile->id(), $loaded_profile->id());

    // Any property read computes the list, not only 'entity'.
    $entity_type_manager->getStorage('profile')->resetCache();
    $entity_type_manager->getStorage('user')->resetCache();
    $user = $entity_type_manager->getStorage('user')->load($user->id());
    $fiber = new \Fiber(fn() => $user->test_profiles->target_id);
    $fiber->start();
    $this->assertEquals($profile->id(), $user->test_profiles->target_id);

    // isset() goes through __isset(), which computes the list as well.
    $entity_type_manager->getStorage('profile')->resetCache();
    $entity_type_manager->getStorage('user')->resetCache();
    $user = $entity_type_manager->getStorage('user')->load($user->id());
    $fiber = new \Fiber(fn() => isset($user->test_profiles->entity));
    $fiber->start();
    $this->assertTrue(isset($user->test_profiles->entity));
  }

}
