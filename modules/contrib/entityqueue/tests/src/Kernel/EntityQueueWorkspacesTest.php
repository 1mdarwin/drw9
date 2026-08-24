<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workspaces\Kernel\WorkspaceTestTrait;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the entityqueue Views relationship inside workspaces.
 *
 * A subqueue is a revisionable, publishable entity, so editing its membership
 * or order in a workspace stores a pending revision rather than changing live.
 * The relationship is a reverse field join that core's Workspaces query alter
 * does not handle, so the handler resolves the active revision itself. These
 * tests cover the result across live vs workspace, required (INNER) vs optional
 * (LEFT) join, single vs multiple queues, the in-queue filter and the position
 * sort, plus entity-level isolation and publishing.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueueWorkspacesTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'filter',
    'text',
    'entityqueue',
  ];

  /**
   * Nodes keyed by a readable name.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_subqueue');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter']);
    $this->createContentType(['type' => 'article']);

    foreach (['a', 'b', 'c'] as $name) {
      $this->nodes[$name] = $this->createNode(['type' => 'article', 'title' => $name]);
    }

    // Live baseline: queue A holds 'a', queue B holds 'b', 'c' is in neither.
    $this->createQueue('queue_a', [$this->nodes['a']]);
    $this->createQueue('queue_b', [$this->nodes['b']]);

    // Enable Workspaces after the live content exists, so the subqueues start
    // as default content and get a 'workspace' revision field added.
    $this->initializeWorkspacesModule();
  }

  /**
   * The relationship returns the right rows across every combination.
   *
   * Queue A is [a] on live and [c] on stage. Node 'b' is only in queue B. The
   * cases cover live vs stage, required (INNER) vs optional (LEFT) join, and
   * the in-queue filter set to "in queue"/"not in queue" on the optional join.
   *
   * @param string|null $workspace
   *   The workspace to assert in, or NULL for live.
   * @param bool $required
   *   Whether the relationship is required (INNER join).
   * @param int|null $filter
   *   The in-queue filter value (1 = in queue, 0 = not in queue), or NULL for
   *   no filter.
   * @param string[] $expected
   *   The node titles expected in the result.
   */
  #[DataProvider('membershipCases')]
  public function testRelationshipMembership(?string $workspace, bool $required, ?int $filter, array $expected): void {
    // Move queue A to [c] on stage.
    $this->switchToWorkspace('stage');
    $this->setQueueItems('queue_a', [$this->nodes['c']]);
    $this->switchToLive();

    if ($workspace !== NULL) {
      $this->switchToWorkspace($workspace);
    }

    $this->buildView([
      'required' => $required,
      'limit_queue' => ['queue_a'],
      'filter' => $filter,
    ]);
    $this->assertSame($expected, $this->resultTitles());
  }

  /**
   * Data provider for ::testRelationshipMembership().
   *
   * @return array<string, array{string|null, bool, int|null, string[]}>
   *   Cases keyed by a readable label.
   */
  public static function membershipCases(): array {
    return [
      // Required (INNER) join: rows are exactly the queue members.
      'live, required' => [NULL, TRUE, NULL, ['a']],
      'stage, required' => ['stage', TRUE, NULL, ['c']],

      // Optional (LEFT) join, no filter: every node is returned regardless of
      // membership, so a target that is only a member on another revision is
      // kept rather than dropped.
      'live, optional, all rows' => [NULL, FALSE, NULL, ['a', 'b', 'c']],
      'stage, optional, all rows' => ['stage', FALSE, NULL, ['a', 'b', 'c']],

      // Optional join + "in queue = Yes": only members.
      'live, optional, in queue' => [NULL, FALSE, 1, ['a']],
      'stage, optional, in queue' => ['stage', FALSE, 1, ['c']],

      // Optional join + "in queue = No": only non-members.
      'live, optional, not in queue' => [NULL, FALSE, 0, ['b', 'c']],
      'stage, optional, not in queue' => ['stage', FALSE, 0, ['a', 'b']],
    ];
  }

  /**
   * Limiting and multi-queue relationships resolve per workspace.
   *
   * On stage queue A becomes [a, c] and queue B is emptied. A relationship
   * limited to queue A ignores the queue B change; a relationship spanning both
   * returns the union of their workspace membership.
   *
   * @param array $limit_queue
   *   The queues the relationship is limited to.
   * @param string|null $workspace
   *   The workspace to assert in, or NULL for live.
   * @param string[] $expected
   *   The node titles expected in the result.
   */
  #[DataProvider('limitingCases')]
  public function testQueueLimiting(array $limit_queue, ?string $workspace, array $expected): void {
    $this->switchToWorkspace('stage');
    $this->setQueueItems('queue_a', [$this->nodes['a'], $this->nodes['c']]);
    $this->setQueueItems('queue_b', []);
    $this->switchToLive();

    if ($workspace !== NULL) {
      $this->switchToWorkspace($workspace);
    }

    $this->buildView(['required' => TRUE, 'limit_queue' => $limit_queue]);
    $this->assertSame($expected, $this->resultTitles());
  }

  /**
   * Data provider for ::testQueueLimiting().
   *
   * @return array<string, array{array, string|null, string[]}>
   *   Cases keyed by a readable label.
   */
  public static function limitingCases(): array {
    $queue_a = ['queue_a'];
    $both = ['queue_a', 'queue_b'];
    return [
      'queue A, live' => [$queue_a, NULL, ['a']],
      'queue A, stage' => [$queue_a, 'stage', ['a', 'c']],
      'both queues, live' => [$both, NULL, ['a', 'b']],
      // Queue A is [a, c] and queue B is empty on stage.
      'both queues, stage' => [$both, 'stage', ['a', 'c']],
    ];
  }

  /**
   * The position sort orders rows by each workspace's pending order.
   */
  public function testRelationshipOrder(): void {
    // Live: queue A holds 'a' then 'b'. Stage: reversed to 'b' then 'a'.
    $this->setQueueItems('queue_a', [$this->nodes['a'], $this->nodes['b']]);
    $this->switchToWorkspace('stage');
    $this->setQueueItems('queue_a', [$this->nodes['b'], $this->nodes['a']]);

    $options = ['required' => TRUE, 'limit_queue' => ['queue_a'], 'position_sort' => TRUE];

    $this->buildView($options);
    $this->assertSame(['b', 'a'], $this->orderedResultTitles());

    $this->switchToLive();
    $this->buildView($options);
    $this->assertSame(['a', 'b'], $this->orderedResultTitles());
  }

  /**
   * Two relationships in one view are each resolved against their own queue.
   */
  public function testMultipleRelationships(): void {
    // On stage, both queues become [c].
    $this->switchToWorkspace('stage');
    $this->setQueueItems('queue_a', [$this->nodes['c']]);
    $this->setQueueItems('queue_b', [$this->nodes['c']]);

    // The first (required) relationship on queue A drives the rows; the second
    // (optional) on queue B must still resolve to its own workspace revision.
    $display_options = [
      'fields' => ['title' => $this->titleField()],
      'relationships' => [
        'q_rel' => $this->relationshipConfig('q_rel', 'queue_a', required: TRUE),
        'q_rel_1' => $this->relationshipConfig('q_rel_1', 'queue_b', required: FALSE),
      ],
    ];

    $this->saveView($display_options);
    $this->assertSame(['c'], $this->resultTitles());

    $this->switchToLive();
    $this->saveView($display_options);
    $this->assertSame(['a'], $this->resultTitles());
  }

  /**
   * A subqueue edit in a workspace is isolated until the workspace publishes.
   */
  public function testSubqueueWorkspaceLifecycle(): void {
    $this->switchToWorkspace('stage');

    // A queue not yet edited in the workspace shows its live members there: it
    // has no workspace revision, so it falls back to its default revision.
    $this->buildView(['required' => TRUE, 'limit_queue' => ['queue_a']]);
    $this->assertSame(['a'], $this->resultTitles());

    // Add 'c' to queue A on stage; live keeps only 'a'.
    $this->setQueueItems('queue_a', [$this->nodes['a'], $this->nodes['c']]);
    $this->assertSame(['a', 'c'], $this->subqueueItemTitles('queue_a'));

    $this->switchToLive();
    $this->assertSame(['a'], $this->subqueueItemTitles('queue_a'));

    // The relationship reflects the same isolation.
    $this->buildView(['required' => TRUE, 'limit_queue' => ['queue_a']]);
    $this->assertSame(['a'], $this->resultTitles());

    // Publishing stage moves the change to live, at both entity and view level.
    $this->workspaces['stage']->publish();
    $this->assertSame(['a', 'c'], $this->subqueueItemTitles('queue_a'));
    $this->assertSame(['a', 'c'], $this->resultTitles());
  }

  /**
   * Creates a simple node queue and fills it with the given nodes.
   */
  protected function createQueue(string $id, array $nodes): void {
    $queue = EntityQueue::create([
      'id' => $id,
      'label' => $id,
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'node',
      ],
    ]);
    $queue->save();

    $this->setQueueItems($id, $nodes);
  }

  /**
   * Sets a subqueue's items to exactly the given nodes, in order.
   */
  protected function setQueueItems(string $id, array $nodes): void {
    $subqueue = EntitySubqueue::load($id);
    $subqueue->set('items', array_map(fn (Node $node) => $node->id(), $nodes));
    $subqueue->save();
  }

  /**
   * Returns the titles of a subqueue's items, in queue order.
   *
   * @return string[]
   *   The referenced node titles.
   */
  protected function subqueueItemTitles(string $id): array {
    $subqueue = EntitySubqueue::load($id);
    return array_map(fn ($node) => $node->label(), $subqueue->get('items')->referencedEntities());
  }

  /**
   * Executes the test view and returns its result node titles, sorted.
   *
   * @return string[]
   *   The node titles in the result.
   */
  protected function resultTitles(): array {
    $titles = $this->orderedResultTitles();
    sort($titles);
    return $titles;
  }

  /**
   * Executes the test view and returns its result node titles, in row order.
   *
   * @return string[]
   *   The node titles in the result.
   */
  protected function orderedResultTitles(): array {
    $view = Views::getView('eq_ws_test');
    $view->execute();
    return array_map(fn ($row) => Node::load($row->nid)->label(), $view->result);
  }

  /**
   * Builds and saves the test view with a single queue relationship.
   *
   * @param array $opts
   *   View options: 'required' (bool), 'limit_queue' (array), 'filter'
   *   (int|null in-queue filter value) and 'position_sort' (bool).
   */
  protected function buildView(array $opts): void {
    $relationship = $this->relationshipConfig('q_rel', '', required: $opts['required'] ?? TRUE);
    $relationship['limit_queue'] = $opts['limit_queue'] ?? [];

    $display_options = [
      'fields' => ['title' => $this->titleField()],
      'relationships' => ['q_rel' => $relationship],
    ];

    if (($opts['filter'] ?? NULL) !== NULL) {
      $display_options['filters'] = ['in_queue' => $this->filterConfig('in_queue', 'q_rel', $opts['filter'])];
    }
    if (!empty($opts['position_sort'])) {
      $display_options['sorts'] = ['pos' => $this->positionSortConfig('pos', 'q_rel')];
    }

    $this->saveView($display_options);
  }

  /**
   * Saves the test view from the given default-display options.
   */
  protected function saveView(array $display_options): void {
    // A view is a config entity, which can only be saved in the default
    // workspace.
    $this->workspaceManager->executeOutsideWorkspace(function () use ($display_options) {
      View::load('eq_ws_test')?->delete();

      View::create([
        'id' => 'eq_ws_test',
        'label' => 'Entityqueue workspaces test',
        'base_table' => 'node_field_data',
        'base_field' => 'nid',
        'display' => [
          'default' => [
            'display_plugin' => 'default',
            'id' => 'default',
            'display_title' => 'Default',
            'position' => 0,
            'display_options' => ['access' => ['type' => 'none']] + $display_options,
          ],
        ],
      ])->save();
    });
  }

  /**
   * Returns the handler config for the node title field.
   */
  protected function titleField(): array {
    return [
      'id' => 'title',
      'table' => 'node_field_data',
      'field' => 'title',
      'plugin_id' => 'field',
      'entity_type' => 'node',
      'entity_field' => 'title',
    ];
  }

  /**
   * Returns the handler config for a queue relationship.
   */
  protected function relationshipConfig(string $id, string $queue_id, bool $required = TRUE): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship',
      'plugin_id' => 'entity_queue',
      'relationship' => 'none',
      'required' => $required,
      'limit_queue' => $queue_id !== '' ? [$queue_id] : [],
    ];
  }

  /**
   * Returns the handler config for an in-queue filter ("No" by default).
   */
  protected function filterConfig(string $id, string $queue_relationship, int $value = 0): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship',
      'plugin_id' => 'entity_queue_in_queue',
      'relationship' => 'none',
      'queue_relationship' => $queue_relationship,
      'value' => $value,
      'group' => 1,
    ];
  }

  /**
   * Returns the handler config for a queue position sort.
   */
  protected function positionSortConfig(string $id, string $queue_relationship, string $order = 'ASC'): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship',
      'plugin_id' => 'entity_queue_position',
      'relationship' => 'none',
      'queue_relationship' => $queue_relationship,
      'order' => $order,
    ];
  }

}
