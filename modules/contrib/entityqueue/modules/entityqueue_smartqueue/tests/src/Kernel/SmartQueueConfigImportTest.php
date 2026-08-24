<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue_smartqueue\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that importing a smartqueue from config creates its subqueues.
 *
 * A smartqueue builds its subqueues in a batch, but a config import runs no
 * batch processor, so the queued operations would never execute. The handler
 * detects the config sync and creates the subqueues synchronously instead, so
 * an imported queue is not left with zero subqueues.
 *
 * @see https://www.drupal.org/project/entityqueue/issues/3238415
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class SmartQueueConfigImportTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entityqueue', 'entityqueue_smartqueue'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
    // The config importer validates that the sync storage carries system.site
    // with the active site UUID, so install it and let copyConfig() mirror it.
    $this->installConfig(['system']);
  }

  /**
   * Tests that a smartqueue imported from config gets one subqueue per entity.
   */
  public function testSubqueuesCreatedOnConfigImport(): void {
    // The target entities exist before the queue is imported, mirroring the
    // reported case where the taxonomy terms are already present when the
    // config lands.
    $entities = [];
    foreach (range(1, 3) as $i) {
      $entity = EntityTest::create(['type' => 'entity_test', 'name' => 'Entity ' . $i]);
      $entity->save();
      $entities[] = $entity;
    }

    // Create the queue, copy the active config to the sync storage, then delete
    // the queue so the import re-creates it. Saving outside a config sync uses
    // the batch, which never runs in a test, so no subqueues exist yet: the
    // assertions below pass only because of the synchronous config-sync path.
    $queue = EntityQueue::create([
      'id' => 'test_queue',
      'label' => 'Test queue',
      'handler' => 'smartqueue',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
      'handler_configuration' => [
        'entity_type' => 'entity_test',
        'bundles' => ['entity_test' => 'entity_test'],
      ],
    ]);
    $queue->save();

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
    $queue->delete();

    // No subqueues exist going into the import.
    foreach ($entities as $entity) {
      $this->assertNull(EntitySubqueue::load('test_queue__' . $entity->id()));
    }

    // Importing the queue config must create one subqueue per target entity.
    $this->configImporter()->import();

    $this->assertNotNull(EntityQueue::load('test_queue'));
    foreach ($entities as $entity) {
      $subqueue = EntitySubqueue::load('test_queue__' . $entity->id());
      $this->assertNotNull($subqueue, sprintf('A subqueue was created for entity %s during config import.', $entity->id()));
      $this->assertSame('test_queue', $subqueue->bundle());
      $this->assertSame($entity->label(), $subqueue->getTitle());
      $this->assertSame((string) $entity->id(), $subqueue->get('attached_entity')->target_id);
    }
  }

}
