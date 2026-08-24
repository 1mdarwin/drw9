<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\Core\Url;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that subqueue route URLs can be built from just the subqueue.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntitySubqueueRouteProcessorTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entityqueue'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Tests subqueue route generation without an explicit 'entity_queue' param.
   *
   * Modules like Entity Clone or Devel add routes keyed on just the subqueue,
   * then ask the menu system to rebuild the subqueue's own tab URLs. Those
   * routes need an 'entity_queue' parameter that isn't in the current path, so
   * generation used to fail with a missing mandatory parameter. The outbound
   * route processor derives it from the subqueue's bundle.
   */
  public function testSubqueueUrlGenerationWithoutQueueParameter(): void {
    $queue = EntityQueue::create([
      'id' => 'test_queue',
      'label' => 'Test queue',
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $queue->save();
    $subqueue = EntitySubqueue::load($queue->id());

    foreach (['canonical', 'edit-form', 'delete-form'] as $rel) {
      $route_name = 'entity.entity_subqueue.' . str_replace('-', '_', $rel);
      // Generate from the route directly with only the subqueue parameter, the
      // way third-party local tasks rebuild sibling tab URLs.
      $url = Url::fromRoute($route_name, ['entity_subqueue' => $subqueue->id()])->toString();
      $this->assertStringContainsString('/entityqueue/' . $queue->id() . '/' . $subqueue->id(), $url);
    }
  }

}
