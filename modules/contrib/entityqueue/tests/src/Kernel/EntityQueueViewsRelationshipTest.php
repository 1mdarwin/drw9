<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormState;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the entityqueue Views relationship limited to multiple queues.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueueViewsRelationshipTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

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

    // Queue A holds 'a', queue B holds 'b', 'c' is in neither.
    $this->createQueue('queue_a', [$this->nodes['a']]);
    $this->createQueue('queue_b', [$this->nodes['b']]);
  }

  /**
   * The relationship's query returns items from exactly the selected queues.
   */
  public function testRelationshipQueryRespectsSelectedQueues(): void {
    // Limited to both queues: items from A and B appear.
    $this->saveRelationshipView(['queue_a' => 'queue_a', 'queue_b' => 'queue_b']);
    $this->assertSame(['a', 'b'], $this->resultTitles());

    // queue_b unchecked (stored as '0' by the checkboxes element): only A's.
    $this->saveRelationshipView(['queue_a' => 'queue_a', 'queue_b' => '0']);
    $this->assertSame(['a'], $this->resultTitles());
  }

  /**
   * The relationship exposes the config tag of every queue it is limited to.
   *
   * Subqueue content invalidation is provided by Views (it loads the referenced
   * entity_subqueue per row), so the handler only contributes the queue config
   * tags, merged rather than overwritten across multiple queues.
   */
  public function testRelationshipCacheTags(): void {
    $this->saveRelationshipView(['queue_a' => 'queue_a', 'queue_b' => 'queue_b']);
    $expected = Cache::mergeTags(
      EntityQueue::load('queue_a')->getCacheTags(),
      EntityQueue::load('queue_b')->getCacheTags(),
    );
    $this->assertEqualsCanonicalizing($expected, $this->relationshipHandler()->getCacheTags());

    // No queue selected: the handler adds no config tags.
    $this->saveRelationshipView([]);
    $this->assertSame([], $this->relationshipHandler()->getCacheTags());
  }

  /**
   * The options form pre-checks selected queues and drops the unchecked '0'.
   */
  public function testOptionsFormPreChecksSelectedQueues(): void {
    // queue_a is unchecked ('0'), queue_b is selected.
    $this->saveRelationshipView(['queue_a' => '0', 'queue_b' => 'queue_b']);

    $form = [];
    $this->relationshipHandler()->buildOptionsForm($form, new FormState());

    // A clean list of only the checked queue, so a legacy scalar would also
    // pre-check correctly.
    $this->assertSame(['queue_b'], $form['limit_queue']['#default_value']);
  }

  /**
   * The contextual link targets the first selected queue, ignoring unchecked.
   */
  public function testContextualLinkUsesFirstSelectedQueue(): void {
    // queue_a is unchecked ('0') and listed first; queue_b is the real choice.
    $this->saveRelationshipView(['queue_a' => '0', 'queue_b' => 'queue_b']);

    $view = Views::getView('eq_rel_test');
    $view->setDisplay('default');
    // The hook only acts when admin links are enabled; set it in memory.
    $view->display_handler->setOption('show_admin_links', TRUE);
    $view->execute();

    \Drupal::moduleHandler()->invoke('entityqueue', 'views_pre_render', [$view]);

    $this->assertArrayHasKey('entityqueue', $view->element['#contextual_links']);
    $this->assertSame(
      'queue_b',
      $view->element['#contextual_links']['entityqueue']['route_parameters']['entity_queue'],
    );
  }

  /**
   * Executes the test view and returns its result node titles, sorted.
   *
   * @return string[]
   *   The node titles in the result.
   */
  protected function resultTitles(): array {
    $view = Views::getView('eq_rel_test');
    $view->execute();
    $titles = array_map(fn ($row) => Node::load($row->nid)->label(), $view->result);
    sort($titles);
    return $titles;
  }

  /**
   * Returns the initialized entity queue relationship handler from the view.
   *
   * @return \Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship
   *   The relationship handler.
   */
  protected function relationshipHandler(): EntityQueueRelationship {
    $view = Views::getView('eq_rel_test');
    $view->setDisplay('default');
    $view->initHandlers();
    return $view->relationship['q_rel'];
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
   * Saves a view with one required relationship limited to the given queues.
   */
  protected function saveRelationshipView(array $limit_queue): void {
    View::load('eq_rel_test')?->delete();

    View::create([
      'id' => 'eq_rel_test',
      'label' => 'Entityqueue relationship test',
      'base_table' => 'node_field_data',
      'base_field' => 'nid',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Default',
          'position' => 0,
          'display_options' => [
            'access' => ['type' => 'none'],
            'fields' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'field',
                'entity_type' => 'node',
                'entity_field' => 'title',
              ],
            ],
            'relationships' => [
              'q_rel' => [
                'id' => 'q_rel',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue',
                'relationship' => 'none',
                'required' => TRUE,
                'limit_queue' => $limit_queue,
              ],
            ],
          ],
        ],
      ],
    ])->save();
  }

}
