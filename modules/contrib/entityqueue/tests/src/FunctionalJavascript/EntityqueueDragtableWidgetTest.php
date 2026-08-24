<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\FunctionalJavascript;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the entityqueue dragtable widget's AJAX interactions.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityqueueDragtableWidgetTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'entityqueue'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Nodes available to queue, keyed by a readable name.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    foreach (['First', 'Second'] as $title) {
      $this->nodes[$title] = $this->createNode(['type' => 'article', 'title' => "$title article"]);
    }

    $this->drupalLogin($this->drupalCreateUser(['administer entityqueue', 'access content']));
  }

  /**
   * Tests adding, clearing, the invalid-reference error and removing.
   */
  public function testAddInvalidAndRemove(): void {
    $this->createNodeQueue('q');
    // Start with one item so the invalid add below is the very first ajax
    // interaction on the page (regression: the error must show then, not only
    // after a later interaction).
    EntitySubqueue::load('q')->set('items', [$this->nodes['First']])->save();
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalGet('/admin/structure/entityqueue/q/q');
    $this->assertCount(1, $this->removeButtons());

    // First interaction: a "label (id)" input whose id matches no entity is
    // rejected with an error shown in the same response, and adds no row.
    $this->addItem('Second article (999)');
    $assert->pageTextContains('The referenced entity (node: 999) does not exist.');
    $this->assertCount(1, $this->removeButtons());

    // A valid item can then be added, and the add control clears.
    $this->addItem('Second article (' . $this->nodes['Second']->id() . ')');
    $assert->pageTextContains('Second article');
    $this->assertCount(2, $this->removeButtons());
    $assert->fieldValueEquals('items[add_more][new_item][target_id]', '');

    // Removing the first row leaves only the second.
    $page->pressButton('items_0_remove_button');
    $assert->assertWaitOnAjaxRequest();
    $this->assertCount(1, $this->removeButtons());

    $page->pressButton('Save');
    // The save reloads the subqueue form; only the second item remains.
    $assert->waitForElement('css', '.messages, [data-drupal-messages]');
    $this->assertSame([(int) $this->nodes['Second']->id()], $this->queuedIds('q'));
  }

  /**
   * Tests that the add control follows the queue's maximum size.
   */
  public function testAddControlFollowsMaxSize(): void {
    $this->createNodeQueue('max_queue', 2);
    EntitySubqueue::load('max_queue')->set('items', [$this->nodes['First']])->save();
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalGet('/admin/structure/entityqueue/max_queue/max_queue');
    // Below the maximum: the add control is shown.
    $assert->buttonExists('Add item');

    // Filling the queue to its maximum hides the add control.
    $this->addItem('Second article (' . $this->nodes['Second']->id() . ')');
    $this->assertCount(2, $this->removeButtons());
    $assert->buttonNotExists('Add item');

    // Removing an item restores the add control.
    $page->pressButton('items_0_remove_button');
    $assert->assertWaitOnAjaxRequest();
    $this->assertCount(1, $this->removeButtons());
    $assert->buttonExists('Add item');
  }

  /**
   * Fills the 'add item' autocomplete and adds it through the widget.
   *
   * @param string $value
   *   The autocomplete value, formatted as "label (id)".
   */
  protected function addItem(string $value): void {
    $page = $this->getSession()->getPage();
    $page->fillField('items[add_more][new_item][target_id]', $value);
    $page->pressButton('Add item');
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Returns the widget's per-row 'Remove' buttons on the current page.
   *
   * @return array
   *   The remove button elements.
   */
  protected function removeButtons(): array {
    return $this->getSession()->getPage()->findAll('css', 'input[name$="_remove_button"]');
  }

  /**
   * Returns the target IDs queued in a subqueue, as integers.
   *
   * @param string $id
   *   The subqueue ID.
   *
   * @return int[]
   *   The queued entity IDs, in order.
   */
  protected function queuedIds(string $id): array {
    $items = EntitySubqueue::load($id)->get('items')->getValue();
    return array_map('intval', array_column($items, 'target_id'));
  }

  /**
   * Creates a simple node queue.
   *
   * @param string $id
   *   The queue ID.
   * @param int $max_size
   *   The maximum number of items, or 0 for no limit.
   */
  protected function createNodeQueue(string $id, int $max_size = 0): void {
    EntityQueue::create([
      'id' => $id,
      'label' => ucfirst($id),
      'handler' => 'simple',
      'entity_settings' => ['target_type' => 'node', 'handler' => 'default:node'],
      'queue_settings' => ['min_size' => 0, 'max_size' => $max_size, 'act_as_queue' => FALSE, 'reverse' => FALSE],
    ])->save();
  }

}
