<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue_smartqueue\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the navigation from an entity to its smartqueue subqueues.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class SmartQueueNavigationTest extends EntityKernelTestBase {

  use UserCreationTrait;

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
  }

  /**
   * Tests the entity operation: one per queue, and gated on subqueue access.
   */
  public function testEntityOperation(): void {
    $this->setUpCurrentUser([], ['manipulate all entityqueues']);

    $this->createSmartqueue('first');
    $this->createSmartqueue('second');

    $entity = EntityTest::create(['name' => 'Test entity']);
    $entity->save();

    // The insert hook auto-creates one subqueue per smartqueue.
    $this->assertNotNull(EntitySubqueue::load('first__' . $entity->id()));
    $this->assertNotNull(EntitySubqueue::load('second__' . $entity->id()));

    // A privileged user gets one operation per queue the entity belongs to.
    $operations = $this->getOperations($entity);
    $this->assertArrayHasKey('entityqueue_smartqueue__first', $operations);
    $this->assertArrayHasKey('entityqueue_smartqueue__second', $operations);
    $this->assertSame('Manage first label items', (string) $operations['entityqueue_smartqueue__first']['title']);
    $this->assertSame(
      EntitySubqueue::load('first__' . $entity->id())->toUrl('edit-form')->toString(),
      $operations['entityqueue_smartqueue__first']['url']->toString(),
    );

    // A user who can't edit the subqueues gets no operations.
    $this->setUpCurrentUser([], ['view test entity']);
    $operations = $this->getOperations($entity);
    $this->assertArrayNotHasKey('entityqueue_smartqueue__first', $operations);
    $this->assertArrayNotHasKey('entityqueue_smartqueue__second', $operations);
  }

  /**
   * Tests the redirect route, its access check, the local task, and redirect.
   */
  public function testRedirectRouteAndLocalTask(): void {
    // The HTTP kernel authenticates the redirect request as the anonymous user,
    // so grant the permission to the anonymous role to pass the access check.
    Role::create(['id' => RoleInterface::ANONYMOUS_ID, 'label' => 'Anonymous'])
      ->grantPermission('manipulate all entityqueues')
      ->save();
    $privileged = $this->createUser(['manipulate all entityqueues']);
    $unprivileged = $this->createUser([]);

    $this->createSmartqueue('first');
    $entity = EntityTest::create(['name' => 'Test entity']);
    $entity->save();
    $this->container->get('router.builder')->rebuild();
    $subqueue = EntitySubqueue::load('first__' . $entity->id());

    // A redirect route is registered for the queue.
    $route = $this->container->get('router.route_provider')
      ->getRouteByName('entityqueue_smartqueue.first');
    $this->assertSame('/admin/entityqueue-smartqueue/first/{entity_test}', $route->getPath());
    $this->assertSame('first', $route->getDefault('entityqueue_smartqueue_queue'));
    $this->assertSame('entity_test', $route->getDefault('entityqueue_smartqueue_entity_type'));

    // A local task tab is derived for the queue, off the canonical route.
    $tasks = $this->container->get('plugin.manager.menu.local_task')->getDefinitions();
    $this->assertArrayHasKey('entityqueue_smartqueue.tasks:first', $tasks);
    $this->assertSame('entity.entity_test.canonical', $tasks['entityqueue_smartqueue.tasks:first']['base_route']);
    $this->assertSame('entityqueue_smartqueue.first', $tasks['entityqueue_smartqueue.tasks:first']['route_name']);

    // Route access mirrors subqueue edit access.
    $access_manager = $this->container->get('access_manager');
    $parameters = ['entity_test' => $entity->id()];
    $this->assertTrue($access_manager->checkNamedRoute('entityqueue_smartqueue.first', $parameters, $privileged));
    $this->assertFalse($access_manager->checkNamedRoute('entityqueue_smartqueue.first', $parameters, $unprivileged));

    // The controller redirects to the subqueue edit form.
    $request = Request::create('/admin/entityqueue-smartqueue/first/' . $entity->id());
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertSame(302, $response->getStatusCode());
    $this->assertSame($subqueue->toUrl('edit-form')->toString(), $response->getTargetUrl());

    // Without a subqueue the route is forbidden, even for a privileged user.
    $subqueue->delete();
    $this->assertFalse($access_manager->checkNamedRoute('entityqueue_smartqueue.first', $parameters, $privileged));
  }

  /**
   * Returns the entity operations contributed by hook implementations.
   */
  protected function getOperations(EntityInterface $entity): array {
    return $this->container->get('module_handler')
      ->invokeAll('entity_operation', [$entity, new CacheableMetadata()]);
  }

  /**
   * Creates a smartqueue targeting the entity_test entity type.
   */
  protected function createSmartqueue(string $id): EntityQueue {
    $queue = EntityQueue::create([
      'id' => $id,
      'label' => $id . ' label',
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

    return $queue;
  }

}
