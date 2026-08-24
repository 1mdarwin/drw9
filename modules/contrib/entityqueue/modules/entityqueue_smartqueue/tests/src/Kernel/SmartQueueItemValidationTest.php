<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue_smartqueue\Kernel;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests reference validation on automated smartqueue subqueues.
 *
 * @group entityqueue
 *
 * @see https://www.drupal.org/project/entityqueue/issues/3217222
 */
#[RunTestsInSeparateProcesses]
class SmartQueueItemValidationTest extends EntityKernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'entityqueue', 'entityqueue_smartqueue'];

  /**
   * The automated subqueue attached to a taxonomy term.
   *
   * @var \Drupal\entityqueue\EntitySubqueueInterface
   */
  protected $subqueue;

  /**
   * A published node that can be added to the queue.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('entity_subqueue');
    $this->installSchema('node', ['node_access']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();

    // A smartqueue keeps one automated subqueue per taxonomy term, holding
    // nodes. Adding the term creates the subqueue via hook_entity_insert().
    EntityQueue::create([
      'id' => 'tag_nodes',
      'label' => 'Nodes per tag',
      'handler' => 'smartqueue',
      'entity_settings' => ['target_type' => 'node'],
      'handler_configuration' => [
        'entity_type' => 'taxonomy_term',
        'bundles' => ['tags'],
      ],
    ])->save();

    $term = Term::create(['vid' => 'tags', 'name' => 'Tag']);
    $term->save();
    $this->subqueue = EntitySubqueue::load('tag_nodes__' . $term->id());

    $this->node = $this->createNode(['type' => 'article', 'status' => 1]);

    // Act as an editor who can reference nodes but is not a taxonomy admin.
    $this->setUpCurrentUser([], ['access content', 'bypass node access']);
  }

  /**
   * Tests that the attached entity reference does not block adding items.
   */
  public function testAttachedEntityDoesNotBlockAddingItems(): void {
    // The 'attached_entity' field storage definition sets target_type to
    // 'entity_subqueue' so both integer and string IDs can be stored. The
    // selection handler reads target_type from the storage definition, so
    // without the handler_settings override it queried the entity_subqueue
    // table and reported the attached term as non-referenceable.
    $attached = $this->subqueue->get('attached_entity');
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')
      ->getSelectionHandler($attached->getFieldDefinition(), $this->subqueue);
    $this->assertSame('taxonomy_term', $handler->getConfiguration()['target_type']);

    // With the handler pointing at the real target type, validating the whole
    // subqueue after adding an item produces no violations and the item saves.
    $this->subqueue->addItem($this->node);
    $this->assertCount(0, $this->subqueue->validate());

    $this->subqueue->save();
    $this->assertTrue($this->subqueue->hasItem($this->node));
  }

}
