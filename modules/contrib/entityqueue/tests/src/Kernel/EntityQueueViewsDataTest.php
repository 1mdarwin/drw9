<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that queue changes refresh the entityqueue relationship in Views data.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueueViewsDataTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'text',
    'user',
    'system',
    'views',
    'taxonomy',
    'entityqueue',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Tests that the relationship appears once a queue is created for a type.
   */
  public function testRelationshipAddedWhenQueueCreated() {
    // Prime the Views data cache while no queue targets taxonomy terms.
    $data = $this->container->get('views.views_data')->get('taxonomy_term_field_data');
    $this->assertArrayNotHasKey('entityqueue_relationship', $data);

    $this->createTermQueue();

    // A new request reads the persistent cache. Saving the queue invalidates
    // the 'views_data' tag, so the fresh read rebuilds and exposes the
    // relationship instead of returning stale, relationship-less data.
    $data = $this->freshViewsData()->get('taxonomy_term_field_data');
    $this->assertArrayHasKey('entityqueue_relationship', $data);
    $this->assertSame('entity_queue', $data['entityqueue_relationship']['relationship']['id']);
  }

  /**
   * Tests that the relationship is dropped once the last queue is deleted.
   */
  public function testRelationshipRemovedWhenQueueDeleted() {
    $queue = $this->createTermQueue();

    $data = $this->freshViewsData()->get('taxonomy_term_field_data');
    $this->assertArrayHasKey('entityqueue_relationship', $data);

    $queue->delete();

    $data = $this->freshViewsData()->get('taxonomy_term_field_data');
    $this->assertArrayNotHasKey('entityqueue_relationship', $data);
  }

  /**
   * Creates and saves a simple queue targeting taxonomy terms.
   */
  protected function createTermQueue(): EntityQueue {
    $queue = EntityQueue::create([
      'id' => 'term_queue',
      'label' => 'Term queue',
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ]);
    $queue->save();

    return $queue;
  }

  /**
   * Builds a fresh ViewsData service to model the next request.
   *
   * ViewsData statically caches per request, so a re-read on the existing
   * service would return the in-memory copy and hide the cache invalidation. A
   * new instance reads the persistent cache backend, which honors the
   * 'views_data' tag.
   */
  protected function freshViewsData(): ViewsData {
    return new ViewsData(
      $this->container->get('cache.default'),
      $this->container->get('module_handler'),
      $this->container->get('language_manager'),
    );
  }

}
