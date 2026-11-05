<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests entity subqueue API.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntitySubQueueAPITest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entityqueue'];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Tests the entity subqueue API.
   */
  public function testSubqueueApi() {
    $id = $this->randomMachineName();
    $queue = EntityQueue::create([
      'id' => $id,
      'label' => $this->randomString(),
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $queue->save();
    $subqueue = EntitySubqueue::load($queue->id());
    $entity = EntityTest::create();
    $entity->save();

    $subqueue->addItem($entity)->save();
    $this->assertTrue($subqueue->hasItem($entity));

    // Field widgets append an empty trailing item to the list.
    // @see https://www.drupal.org/project/entityqueue/issues/3618249
    $subqueue->get('items')->appendItem();
    $this->assertTrue($subqueue->hasItem($entity));

    $entity->delete();
    $subqueue = EntitySubqueue::load($queue->id());
    $this->assertFalse($subqueue->hasItem($entity));
  }

  /**
   * Tests that creating a subqueue requires the relevant permission.
   */
  public function testCreateAccess() {
    // The first user (UID 1) bypasses all access checks, so reserve it before
    // creating the accounts under test.
    $this->createUser();

    // A "multiple" handler allows subqueues to be created manually, so create
    // access must fall back to the per-queue permission.
    $multiple = EntityQueue::create([
      'id' => 'multiple_queue',
      'label' => 'Multiple subqueues',
      'handler' => 'multiple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $multiple->save();

    // A "simple" handler has automated subqueues, so they can never be created
    // manually no matter which permissions the user holds.
    $simple = EntityQueue::create([
      'id' => 'simple_queue',
      'label' => 'Simple subqueue',
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $simple->save();

    $access_handler = $this->entityTypeManager->getAccessControlHandler('entity_subqueue');

    // A user without any entityqueue permission must not be able to create a
    // subqueue, even for a queue that supports manual subqueues.
    $unprivileged = $this->createUser();
    $this->assertFalse($access_handler->createAccess('multiple_queue', $unprivileged));

    // Granting the per-queue create permission opens up manual creation.
    $editor = $this->createUser(['create multiple_queue entityqueue']);
    $this->assertTrue($access_handler->createAccess('multiple_queue', $editor));

    // Automated subqueues stay locked even for an administrator.
    $admin = $this->createUser(['administer entityqueue']);
    $this->assertFalse($access_handler->createAccess('simple_queue', $admin));
  }

  /**
   * Tests item order and deltas on a queue that adds new items to the top.
   */
  public function testReversedQueueItems(): void {
    $queue = EntityQueue::create([
      'id' => 'reversed_queue',
      'label' => 'Reversed queue',
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
      'queue_settings' => [
        'min_size' => 0,
        'max_size' => 0,
        'act_as_queue' => FALSE,
        'reverse' => TRUE,
      ],
    ]);
    $queue->save();

    $entities = [];
    $ids = [];
    for ($i = 0; $i < 3; $i++) {
      $entity = EntityTest::create();
      $entity->save();
      $entities[] = $entity;
      $ids[] = (int) $entity->id();
    }

    $subqueue = EntitySubqueue::load($queue->id());
    foreach ($entities as $entity) {
      $subqueue->addItem($entity);
    }
    $subqueue->save();
    $items = EntitySubqueue::load($queue->id())->get('items');
    $this->assertSame(array_reverse($ids), array_map('intval', array_column($items->getValue(), 'target_id')));

    // Generic list operations keep appending. ItemList::set() appends when the
    // delta is new, and widgets append an empty trailing item.
    $items->setValue([$ids[0]]);
    $items->set(1, $ids[1]);
    $items->set(2, $ids[2]);
    $this->assertSame($ids, array_map('intval', array_column($items->getValue(), 'target_id')));
    $items->appendItem();
    $this->assertCount(4, $items);
    $this->assertTrue($items->get(3)->isEmpty());
    foreach ($items as $delta => $item) {
      $this->assertSame($delta, $item->getName());
    }
  }

}
