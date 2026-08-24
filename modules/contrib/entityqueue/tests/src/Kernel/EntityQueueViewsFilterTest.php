<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the entityqueue Views filter with multiple queue relationships.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueueViewsFilterTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

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
  }

  /**
   * Each filter resolves to the relationship it targets.
   *
   * Node 'a' is in queue A, node 'b' is in queue B, node 'c' is in neither.
   */
  public function testFilterTargetsResolveToTheRightQueue(): void {
    $this->createQueue('queue_a', [$this->nodes['a']]);
    $this->createQueue('queue_b', [$this->nodes['b']]);

    // Two filters, each limited to its own queue: "not in A" and "not in B"
    // drop both 'a' and 'b'. The bug bound every filter to the first
    // relationship, so the queue B filter never dropped 'b'.
    $this->createFilterView();
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['c'], $view);

    // One relationship and no queue selected: the filter falls back to that
    // relationship, so "not in A" drops only 'a'.
    $this->createFilterView(single: TRUE);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['b', 'c'], $view);

    // The opposite direction: "in queue A = Yes" keeps only queue A's member.
    $this->saveFilterView(
      ['queue_a_rel' => $this->relationshipConfig('queue_a_rel', 'queue_a')],
      ['in_queue_a' => $this->filterConfig('in_queue_a', 'queue_a_rel', value: 1)],
    );
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['a'], $view);
  }

  /**
   * The position field and sort read their own relationship's queue.
   *
   * Queue A holds 'b' then 'a', so 'b' is position 0 and 'a' is position 1.
   * Both handlers reach the position through the relationship join, the same
   * path the filter uses.
   */
  public function testPositionFieldAndSort(): void {
    $this->createQueue('queue_a', [$this->nodes['b'], $this->nodes['a']]);

    // The field shows each item's position in the queue.
    $this->saveView([
      'relationships' => ['queue_a_rel' => $this->relationshipConfig('queue_a_rel', 'queue_a', required: TRUE)],
      'fields' => ['pos' => $this->positionFieldConfig('pos', 'queue_a_rel')],
    ]);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $positions = [];
    foreach ($view->result as $row) {
      $positions[Node::load($row->nid)->label()] = (int) $view->field['pos']->getValue($row);
    }
    ksort($positions);
    $this->assertSame(['a' => 1, 'b' => 0], $positions);

    // The sort orders by position, so 'b' (0) comes before 'a' (1), not
    // alphabetically.
    $this->saveView([
      'fields' => ['title' => $this->titleField()],
      'relationships' => ['queue_a_rel' => $this->relationshipConfig('queue_a_rel', 'queue_a', required: TRUE)],
      'sorts' => ['pos' => $this->positionSortConfig('pos', 'queue_a_rel')],
    ]);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $titles = array_map(fn ($row) => Node::load($row->nid)->label(), $view->result);
    $this->assertSame(['b', 'a'], $titles);
  }

  /**
   * The position filter limits results to a range of queue positions.
   *
   * Queue A holds 'a', 'b', 'c', so the positions are a=0, b=1, c=2.
   */
  public function testPositionFilter(): void {
    $this->createQueue('queue_a', [$this->nodes['a'], $this->nodes['b'], $this->nodes['c']]);

    // Keep only the first two positions (delta < 2): 'a' and 'b'.
    $this->savePositionFilterView('<', 2);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['a', 'b'], $view);

    // Keep positions six-and-up style range (delta >= 1): 'b' and 'c'.
    $this->savePositionFilterView('>=', 1);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['b', 'c'], $view);

    // An exact position (delta = 2): only 'c'.
    $this->savePositionFilterView('=', 2);
    $view = Views::getView('eq_filter_test');
    $view->execute();
    $this->assertResultTitles(['c'], $view);
  }

  /**
   * Saves the test view with a single position filter on queue A.
   */
  protected function savePositionFilterView(string $operator, int $value): void {
    $this->saveView([
      'fields' => ['title' => $this->titleField()],
      'relationships' => ['queue_a_rel' => $this->relationshipConfig('queue_a_rel', 'queue_a', required: TRUE)],
      'filters' => ['position' => $this->positionFilterConfig('position', 'queue_a_rel', $operator, $value)],
    ]);
  }

  /**
   * Returns the handler config for a queue position filter.
   */
  protected function positionFilterConfig(string $id, string $queue_relationship, string $operator, int $value): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship_position',
      'plugin_id' => 'entity_queue_position',
      'relationship' => 'none',
      'queue_relationship' => $queue_relationship,
      'operator' => $operator,
      'value' => ['value' => $value, 'min' => '', 'max' => ''],
      'group' => 1,
    ];
  }

  /**
   * Admins are warned when a handler can find no entity queue relationship.
   */
  public function testWarnsWhenNoRelationship(): void {
    // A queue must exist so the in-queue filter is available in Views data.
    $this->createQueue('queue_a', []);
    // The warning only shows to users who can administer views.
    $this->setCurrentUser($this->createUser([], NULL, TRUE));

    // A view with the in-queue filter but no entity queue relationship.
    $this->saveView([
      'fields' => ['title' => $this->titleField()],
      'filters' => ['in_queue_a' => $this->filterConfig('in_queue_a', '')],
    ]);
    Views::getView('eq_filter_test')->execute();

    $errors = array_map('strval', \Drupal::messenger()->messagesByType(MessengerInterface::TYPE_ERROR));
    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('Entityqueue', implode("\n", $errors));
  }

  /**
   * The queue relationship selector that replaces the hidden Views dropdown.
   */
  public function testQueueRelationshipSelector(): void {
    $this->createQueue('queue_a', [$this->nodes['a']]);
    $this->createQueue('queue_b', [$this->nodes['b']]);

    // Lists both relationships, keyed by id and labeled by their queue. Built
    // from the stored display config, since the Views UI builds this form
    // without initializing the other handlers (which left the dropdown empty).
    $this->createFilterView();
    $this->assertSame(
      ['queue_a_rel' => 'queue_a', 'queue_b_rel' => 'queue_b'],
      $this->buildSelector()['#options'],
    );

    // A relationship's admin label wins over its queue name.
    $this->saveFilterView(
      [
        'queue_a_rel' => ['admin_label' => 'Primary'] + $this->relationshipConfig('queue_a_rel', 'queue_a'),
        'queue_b_rel' => ['admin_label' => 'Secondary'] + $this->relationshipConfig('queue_b_rel', 'queue_b'),
      ],
      ['in_queue_a' => $this->filterConfig('in_queue_a', 'queue_a_rel')],
    );
    $this->assertSame(
      ['queue_a_rel' => 'Primary', 'queue_b_rel' => 'Secondary'],
      $this->buildSelector()['#options'],
    );

    // With only one relationship there is nothing to choose, so it is omitted.
    $this->createFilterView(single: TRUE);
    $this->assertNull($this->buildSelector());
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

    $subqueue = EntitySubqueue::load($queue->id());
    foreach ($nodes as $node) {
      $subqueue->addItem($node);
    }
    $subqueue->save();
  }

  /**
   * Builds a view with one or two queue relationships and matching filters.
   *
   * @param bool $single
   *   When TRUE, only the queue A relationship and its filter are added, and
   *   the filter selects no queue to exercise the first-relationship fallback.
   */
  protected function createFilterView(bool $single = FALSE): void {
    $relationships = [
      'queue_a_rel' => $this->relationshipConfig('queue_a_rel', 'queue_a'),
    ];
    $filters = [
      'in_queue_a' => $this->filterConfig('in_queue_a', $single ? '' : 'queue_a_rel'),
    ];

    if (!$single) {
      $relationships['queue_b_rel'] = $this->relationshipConfig('queue_b_rel', 'queue_b');
      $filters['in_queue_b'] = $this->filterConfig('in_queue_b', 'queue_b_rel');
    }

    $this->saveFilterView($relationships, $filters);
  }

  /**
   * Builds the in-queue filter form the way the Views UI does.
   *
   * @return array|null
   *   The queue relationship selector element, or NULL when it is omitted.
   */
  protected function buildSelector(): ?array {
    $executable = Views::getView('eq_filter_test');
    $executable->setDisplay('default');
    $handler = $executable->display_handler->getHandler('filter', 'in_queue_a');

    $form = [];
    $handler->buildOptionsForm($form, new FormState());

    return $form['queue_relationship'] ?? NULL;
  }

  /**
   * Saves a view with the given relationships and filters, plus a title field.
   */
  protected function saveFilterView(array $relationships, array $filters): void {
    $this->saveView([
      'fields' => ['title' => $this->titleField()],
      'relationships' => $relationships,
      'filters' => $filters,
    ]);
  }

  /**
   * Saves the test view from the given default-display options.
   */
  protected function saveView(array $display_options): void {
    // Replace any view from an earlier scenario in the same test.
    View::load('eq_filter_test')?->delete();

    View::create([
      'id' => 'eq_filter_test',
      'label' => 'Entityqueue filter test',
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
  protected function relationshipConfig(string $id, string $queue_id, bool $required = FALSE): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship',
      'plugin_id' => 'entity_queue',
      'relationship' => 'none',
      'required' => $required,
      'limit_queue' => [$queue_id],
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
   * Returns the handler config for a queue position field.
   */
  protected function positionFieldConfig(string $id, string $queue_relationship): array {
    return [
      'id' => $id,
      'table' => 'node_field_data',
      'field' => 'entityqueue_relationship_position',
      'plugin_id' => 'entity_queue_position',
      'relationship' => 'none',
      'queue_relationship' => $queue_relationship,
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

  /**
   * Asserts the view returns exactly the nodes with the given titles.
   */
  protected function assertResultTitles(array $expected, ViewExecutable $view): void {
    $titles = [];
    foreach ($view->result as $row) {
      $titles[] = Node::load($row->nid)->label();
    }
    sort($titles);
    sort($expected);
    $this->assertSame($expected, $titles);
  }

}
