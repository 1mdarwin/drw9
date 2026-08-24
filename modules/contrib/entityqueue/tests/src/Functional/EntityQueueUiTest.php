<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Functional;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the user interface for entityqueue module.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueueUiTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['entityqueue_test'];

  /**
   * A user with the 'administer entityqueue' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['administer entityqueue']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests entity queue list page.
   */
  public function testListPage() {
    $this->drupalGet('/admin/structure/entityqueue');
    $this->assertSession()->pageTextContains('There are no disabled queues');
  }

  /**
   * Tests that update-only users can save without an access-denied redirect.
   */
  public function testUpdateOnlyUserCanSaveSubqueue(): void {
    $subqueue = EntitySubqueue::load('simple_queue');
    $this->assertNotNull($subqueue);

    $this->loginAsQueueEditor(['update simple_queue entityqueue']);
    $this->assertSavedWithoutAccessDenied(
      '/admin/structure/entityqueue/simple_queue/simple_queue',
      [],
      '/admin/structure/entityqueue/simple_queue/simple_queue'
    );
  }

  /**
   * Tests update-only save redirect behavior for multiple-subqueue handlers.
   */
  public function testUpdateOnlyUserCanSaveMultiSubqueue(): void {
    $subqueue = EntitySubqueue::create([
      'queue' => 'test_queue',
      'name' => 'test_subqueue',
      'title' => 'Test subqueue',
    ]);
    $subqueue->save();

    $this->loginAsQueueEditor(['update test_queue entityqueue']);
    $this->assertSavedWithoutAccessDenied(
      '/admin/structure/entityqueue/test_queue/test_subqueue',
      [],
      '/admin/structure/entityqueue/test_queue/test_subqueue'
    );
  }

  /**
   * Tests create-only users can save without redirecting to inaccessible pages.
   */
  public function testCreateOnlyUserCanSaveSubqueue(): void {
    $this->loginAsQueueEditor(['create test_queue entityqueue']);
    $this->assertSavedWithoutAccessDenied('/admin/structure/entityqueue/test_queue/add', [
      'title[0][value]' => 'Create only subqueue',
      'name' => 'create_only_subqueue',
    ]);
    $this->assertNotNull(EntitySubqueue::load('create_only_subqueue'));
  }

  /**
   * Tests adding, reordering and removing items through the widget.
   */
  public function testAddReorderRemoveItems(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $first = $this->createNode(['type' => 'article', 'title' => 'First article']);
    $second = $this->createNode(['type' => 'article', 'title' => 'Second article']);
    $this->createNodeQueue('q');

    $url = '/admin/structure/entityqueue/q/q';
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Add item');
    $this->assertCount(0, $this->removeButtons());

    // Adding an item shows it as a row with an edit link and a remove button,
    // and clears the add control for the next entry.
    $this->submitForm(['items[add_more][new_item][target_id]' => 'First article (' . $first->id() . ')'], 'Add item');
    $this->assertSession()->pageTextContains('First article');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->fieldValueEquals('items[add_more][new_item][target_id]', '');
    $this->assertCount(1, $this->removeButtons());

    $this->submitForm(['items[add_more][new_item][target_id]' => 'Second article (' . $second->id() . ')'], 'Add item');
    $this->assertCount(2, $this->removeButtons());

    // Reverse the order via the row weights; saving keeps the new order.
    $this->submitForm(['items[0][_weight]' => 1, 'items[1][_weight]' => 0], 'Save');
    $this->assertSame([(int) $second->id(), (int) $first->id()], $this->queuedIds('q'));

    // Removing the first row (now the second article) leaves the first article.
    $this->drupalGet($url);
    $this->submitForm([], 'items_0_remove_button');
    $this->assertCount(1, $this->removeButtons());
    $this->submitForm([], 'Save');
    $this->assertSame([(int) $first->id()], $this->queuedIds('q'));
  }

  /**
   * Tests the publication-status marker shown on queued items.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/2462447
   */
  public function testPublishStatusMarker(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $published = $this->createNode(['type' => 'article', 'title' => 'Published article']);
    // Title deliberately avoids the word "Unpublished" so the marker assertions
    // below can't be satisfied by the node label alone.
    $hidden = $this->createNode(['type' => 'article', 'title' => 'Hidden article', 'status' => 0]);
    $this->createNodeQueue('q');
    EntitySubqueue::load('q')->set('items', [$published, $hidden])->save();

    // Seeing the unpublished item's label, and therefore its marker, requires
    // access to the unpublished node.
    $this->drupalLogin($this->drupalCreateUser(['administer entityqueue', 'access content', 'bypass node access']));
    $page = $this->getSession()->getPage();
    $url = '/admin/structure/entityqueue/q/q';

    // The default ('unpublished') marks only the unpublished item.
    $this->drupalGet($url);
    $this->assertCount(0, $page->findAll('css', '.entityqueue-item-status--published'));
    $unpublished = $page->findAll('css', '.entityqueue-item-status--unpublished');
    $this->assertCount(1, $unpublished);
    $this->assertSame('Unpublished', $unpublished[0]->getText());

    // 'all' marks every item with its status.
    $this->setItemsWidget('q', 'entityqueue_dragtable', ['show_publish_status' => 'all']);
    $this->drupalGet($url);
    $this->assertCount(1, $page->findAll('css', '.entityqueue-item-status--published'));
    $this->assertCount(1, $page->findAll('css', '.entityqueue-item-status--unpublished'));

    // 'off' shows no markers at all.
    $this->setItemsWidget('q', 'entityqueue_dragtable', ['show_publish_status' => 'off']);
    $this->drupalGet($url);
    $this->assertCount(0, $page->findAll('css', '.entityqueue-item-status'));
  }

  /**
   * Tests maximum-size enforcement, including a queue over a lowered maximum.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/3258972
   */
  public function testMaximumSizeEnforcement(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $queue = $this->createNodeQueue('overfull_queue', 4);

    $nodes = [];
    for ($i = 1; $i <= 4; $i++) {
      $nodes[] = $this->createNode(['type' => 'article', 'title' => "Item $i"]);
    }
    EntitySubqueue::load('overfull_queue')->set('items', $nodes)->save();

    // Lower the maximum to 3, one below the 4 items already queued.
    $queue->set('queue_settings', ['min_size' => 0, 'max_size' => 3, 'act_as_queue' => FALSE, 'reverse' => FALSE])->save();

    $this->drupalGet('/admin/structure/entityqueue/overfull_queue/overfull_queue');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(4, $this->removeButtons());
    // Over the maximum: no further items can be added.
    $this->assertSession()->buttonNotExists('Add item');

    // Saving while over the limit is blocked by the size constraint.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('This queue can not hold more than 3 items.');
    $this->assertCount(4, EntitySubqueue::load('overfull_queue')->get('items'));

    // Removing is not blocked by the field cardinality. At the maximum the add
    // control stays hidden.
    $this->submitForm([], 'items_3_remove_button');
    $this->assertSession()->pageTextNotContains('this field cannot hold more than 3 values');
    $this->assertCount(3, $this->removeButtons());
    $this->assertSession()->buttonNotExists('Add item');

    // Dropping below the maximum restores the add control.
    $this->submitForm([], 'items_0_remove_button');
    $this->assertCount(2, $this->removeButtons());
    $this->assertSession()->buttonExists('Add item');

    // The now-compliant queue saves.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('This queue can not hold more than 3 items.');
    $this->assertCount(2, EntitySubqueue::load('overfull_queue')->get('items'));
  }

  /**
   * Tests the widget on an ordinary, required (non-subqueue) reference field.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/3054631
   */
  public function testWidgetOnRequiredReferenceField(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    FieldStorageConfig::create([
      'field_name' => 'field_refs',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
      'settings' => ['target_type' => 'node'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_refs',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'References',
      'required' => TRUE,
      'settings' => ['handler' => 'default:node'],
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_refs', ['type' => 'entityqueue_dragtable'])
      ->save();

    $target = $this->createNode(['type' => 'article', 'title' => 'Target article']);
    $this->drupalLogin($this->drupalCreateUser(['create article content', 'access content']));

    // The add form must render (the widget is not subqueue-specific).
    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Add item');

    // Adding an item must not trip the empty field's required constraint.
    $this->submitForm(['field_refs[add_more][new_item][target_id]' => 'Target article (' . $target->id() . ')'], 'Add item');
    $this->assertSession()->pageTextNotContains('This value should not be null.');
    $this->assertSession()->pageTextContains('Target article');
    $this->assertCount(1, $this->removeButtons());
  }

  /**
   * Tests that adding a reference to a non-existent entity is rejected.
   */
  public function testAddNonexistentReference(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->createNode(['type' => 'article', 'title' => 'Real article']);
    $this->createNodeQueue('q');

    $this->drupalGet('/admin/structure/entityqueue/q/q');

    // A hand-edited "label (id)" input whose id matches no entity is rejected
    // rather than added as an empty row.
    $this->submitForm(['items[add_more][new_item][target_id]' => 'Real article (999)'], 'Add item');
    $this->assertSession()->pageTextContains('The referenced entity (node: 999) does not exist.');
    $this->assertCount(0, $this->removeButtons());
  }

  /**
   * Tests adding an auto-created entity through the widget.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/3395293
   */
  public function testAddAutoCreatedItem(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $existing = $this->createNode(['type' => 'article', 'title' => 'Existing article']);

    // A queue whose selection handler is allowed to create the referenced node.
    EntityQueue::create([
      'id' => 'q',
      'label' => 'q',
      'handler' => 'simple',
      'entity_settings' => [
        'target_type' => 'node',
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => ['article' => 'article'],
          'auto_create' => TRUE,
        ],
      ],
      'queue_settings' => ['min_size' => 0, 'max_size' => 0, 'act_as_queue' => FALSE, 'reverse' => FALSE],
    ])->save();

    // 'access content' lets the new row show the node label instead of
    // '- Restricted access -'.
    $this->drupalLogin($this->drupalCreateUser(['administer entityqueue', 'access content']));

    $this->drupalGet('/admin/structure/entityqueue/q/q');
    $this->assertSession()->statusCodeEquals(200);

    // Typing a label that matches no existing node auto-creates one and adds it
    // as a queued row, rather than failing with an AJAX/500 error. The unsaved
    // item shows as an editable autocomplete until the subqueue is saved.
    $this->submitForm(['items[add_more][new_item][target_id]' => 'A brand new article'], 'Add item');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('items[0][target_id]', 'A brand new article');
    $this->assertCount(1, $this->removeButtons());

    // Add an existing node alongside the still-unsaved auto-created one.
    $this->submitForm(['items[add_more][new_item][target_id]' => 'Existing article (' . $existing->id() . ')'], 'Add item');
    $this->assertCount(2, $this->removeButtons());

    // Reverse via the action button, then save. The unsaved auto-created item
    // (row 0) must survive the entity-based reorder and land in its new place.
    $this->submitForm([], 'Reverse');
    $this->submitForm([], 'Save');

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'A brand new article']);
    $this->assertCount(1, $nodes);
    $created = reset($nodes);
    $this->assertSame([(int) $existing->id(), (int) $created->id()], $this->queuedIds('q'));
  }

  /**
   * Tests the 'Reverse', 'Shuffle' and 'Clear' actions on the subqueue form.
   *
   * The actions reorder the submitted items, so they must behave the same with
   * any entity reference widget.
   */
  #[DataProvider('providerItemsWidget')]
  public function testSubqueueActions(string $widget_type): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $ids = [];
    foreach (['First', 'Second', 'Third'] as $title) {
      $ids[] = (int) $this->createNode(['type' => 'article', 'title' => $title])->id();
    }

    $this->createNodeQueue('q');
    $this->setItemsWidget('q', $widget_type);

    // Seed the auto-created subqueue with the three nodes in order.
    EntitySubqueue::load('q')->set('items', $ids)->save();

    $this->assertReverseShuffleClear('q', $ids);
  }

  /**
   * Provides the items field widgets the subqueue actions must work with.
   */
  public static function providerItemsWidget(): array {
    return [
      'draggable table widget' => ['entityqueue_dragtable'],
      'core autocomplete widget' => ['entity_reference_autocomplete'],
    ];
  }

  /**
   * Tests the subqueue actions when the items field uses an IEF widget.
   *
   * The inline entity form widget keeps the referenced entities in its own
   * form-state storage, which the actions must reorder alongside the field.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/2657680
   */
  public function testSubqueueActionsWithInlineEntityFormWidget(): void {
    \Drupal::service('module_installer')->install(['inline_entity_form']);

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $ids = [];
    foreach (['First', 'Second', 'Third'] as $title) {
      $ids[] = (int) $this->createNode(['type' => 'article', 'title' => $title])->id();
    }

    $this->createNodeQueue('q');
    $this->setItemsWidget('q', 'inline_entity_form_complex', [
      'form_mode' => 'default',
      'allow_new' => TRUE,
      'allow_existing' => TRUE,
      'match_operator' => 'CONTAINS',
    ]);

    EntitySubqueue::load('q')->set('items', $ids)->save();

    $this->assertReverseShuffleClear('q', $ids);
  }

  /**
   * Asserts the Reverse, Shuffle and Clear actions on a subqueue edit form.
   *
   * @param string $queue_id
   *   The queue (and, for a simple queue, subqueue) ID.
   * @param int[] $ids
   *   The seeded item IDs, in order.
   */
  protected function assertReverseShuffleClear(string $queue_id, array $ids): void {
    $url = '/admin/structure/entityqueue/' . $queue_id . '/' . $queue_id;

    // Reverse, then save, keeps the reversed order.
    $this->drupalGet($url);
    $this->submitForm([], 'Reverse');
    $this->submitForm([], 'Save');
    $this->assertSame(array_reverse($ids), $this->queuedIds($queue_id));

    // Shuffle keeps the same set of items.
    $this->drupalGet($url);
    $this->submitForm([], 'Shuffle');
    $this->submitForm([], 'Save');
    $shuffled = $this->queuedIds($queue_id);
    $this->assertCount(count($ids), $shuffled);
    $this->assertEqualsCanonicalizing($ids, $shuffled);

    // Clear empties the queue.
    $this->drupalGet($url);
    $this->submitForm([], 'Clear');
    $this->submitForm([], 'Save');
    $this->assertSame([], $this->queuedIds($queue_id));
  }

  /**
   * Tests that enabling auto-create requires a concrete destination bundle.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/3395293
   */
  public function testAutoCreateRequiresBundle(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    $url = '/admin/structure/entityqueue/simple_queue/edit';
    $error = 'To create referenced entities, select at least one bundle to create them in.';

    // Enabling auto-create without selecting a bundle ("all bundles") is
    // rejected, since the widget can't tell which bundle to create and would
    // otherwise create items with the entity type ID as their bundle.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(['entity_settings[handler_settings][auto_create]' => TRUE], 'Save');
    $this->assertSession()->pageTextContains($error);

    // Selecting a single bundle makes it valid and the setting is stored.
    $this->drupalGet($url);
    $this->submitForm([
      'entity_settings[handler_settings][target_bundles][article]' => 'article',
      'entity_settings[handler_settings][auto_create]' => TRUE,
    ], 'Save');
    $this->assertSession()->pageTextNotContains($error);

    $settings = EntityQueue::load('simple_queue')->getEntitySettings();
    $this->assertTrue($settings['handler_settings']['auto_create']);
    $this->assertSame(['article' => 'article'], $settings['handler_settings']['target_bundles']);
  }

  /**
   * Tests the queue-level local task tabs on the various queue pages.
   *
   * @see https://www.drupal.org/project/entityqueue/issues/3509515
   */
  public function testQueueManagementTabs(): void {
    \Drupal::service('module_installer')->install(['block', 'field_ui']);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalLogin($this->drupalCreateUser([
      'administer entityqueue',
      'administer entity_subqueue fields',
      'administer entity_subqueue form display',
      'administer entity_subqueue display',
    ]));

    // A queue with multiple subqueues exposes the queue-level tabs on its
    // subqueue list page.
    $this->drupalGet('/admin/structure/entityqueue/test_queue/list');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPrimaryTabsContain(['Subqueues', 'Configure', 'Manage fields', 'Manage form display', 'Manage display']);

    // The same tabs appear on the queue's 'Configure' (edit) page.
    $this->drupalGet('/admin/structure/entityqueue/test_queue/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPrimaryTabsContain(['Subqueues', 'Configure', 'Manage fields', 'Manage form display', 'Manage display']);

    // An individual subqueue of a multiple-subqueue queue keeps its own tabs
    // and does not gain the queue-level ones.
    $subqueue = EntitySubqueue::create([
      'queue' => 'test_queue',
      'name' => 'test_subqueue',
      'title' => 'Test subqueue',
    ]);
    $subqueue->save();
    $this->drupalGet('/admin/structure/entityqueue/test_queue/test_subqueue');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPrimaryTabsContain(['Edit', 'Delete']);
    $this->assertSession()->elementNotExists('xpath', $this->primaryTabXpath('Configure'));
    $this->assertSession()->elementNotExists('xpath', $this->primaryTabXpath('Manage fields'));

    // A simple queue lands on its single subqueue's edit form ('Edit items'),
    // which exposes the queue-level tabs.
    $this->drupalGet('/admin/structure/entityqueue/simple_queue/simple_queue');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPrimaryTabsContain([
      'Edit items',
      'Configure',
      'Manage fields',
      'Manage form display',
      'Manage display',
    ]);

    // The simple queue's Configure page shows the same 'Edit items' tab
    // (pointing at the single subqueue) instead of a 'Subqueues' tab.
    $this->drupalGet('/admin/structure/entityqueue/simple_queue/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertPrimaryTabsContain([
      'Edit items',
      'Configure',
      'Manage fields',
      'Manage form display',
      'Manage display',
    ]);
    $this->assertSession()->elementNotExists('xpath', $this->primaryTabXpath('Subqueues'));
  }

  /**
   * Asserts that the given titles appear among the primary local task tabs.
   *
   * @param string[] $expected
   *   The expected tab titles.
   */
  protected function assertPrimaryTabsContain(array $expected): void {
    foreach ($expected as $title) {
      $this->assertSession()->elementExists('xpath', $this->primaryTabXpath($title));
    }
  }

  /**
   * Builds an xpath matching a primary local task tab by its title.
   *
   * @param string $title
   *   The tab title.
   *
   * @return string
   *   The xpath expression.
   */
  protected function primaryTabXpath(string $title): string {
    // The stark theme uses the default template, which renders the primary
    // tabs as the first <ul> following the "Primary tabs" heading.
    return '//h2[normalize-space(string(.)) = "Primary tabs"]/following-sibling::ul[1]//a[normalize-space(string(.)) = "' . $title . '"]';
  }

  /**
   * Creates a simple node queue.
   *
   * @param string $id
   *   The queue ID.
   * @param int $max_size
   *   The maximum number of items, or 0 for no limit.
   *
   * @return \Drupal\entityqueue\Entity\EntityQueue
   *   The saved queue.
   */
  protected function createNodeQueue(string $id, int $max_size = 0): EntityQueue {
    $queue = EntityQueue::create([
      'id' => $id,
      'label' => $id,
      'handler' => 'simple',
      'entity_settings' => ['target_type' => 'node', 'handler' => 'default:node'],
      'queue_settings' => ['min_size' => 0, 'max_size' => $max_size, 'act_as_queue' => FALSE, 'reverse' => FALSE],
    ]);
    $queue->save();

    return $queue;
  }

  /**
   * Sets the widget used by a subqueue bundle's 'items' field.
   *
   * @param string $bundle
   *   The subqueue bundle (queue ID).
   * @param string $type
   *   The field widget plugin ID.
   * @param array $settings
   *   Optional widget settings.
   */
  protected function setItemsWidget(string $bundle, string $type, array $settings = []): void {
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_subqueue', $bundle)
      ->setComponent('items', ['type' => $type, 'settings' => $settings, 'weight' => 5])
      ->save();
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
   * Logs in a queue editor with the given permissions.
   *
   * @param array $permissions
   *   The permissions to grant.
   */
  protected function loginAsQueueEditor(array $permissions): void {
    $queue_editor = $this->drupalCreateUser($permissions);
    $this->drupalLogin($queue_editor);
  }

  /**
   * Opens a form, submits save, and asserts no access denied after submit.
   *
   * @param string $path
   *   The form path to open.
   * @param array $values
   *   Optional form values to submit.
   * @param string|null $expected_path_suffix
   *   Optional URL suffix expected after save.
   */
  protected function assertSavedWithoutAccessDenied(string $path, array $values = [], ?string $expected_path_suffix = NULL): void {
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($values, 'Save');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Access denied');
    if ($expected_path_suffix !== NULL) {
      $this->assertStringEndsWith($expected_path_suffix, $this->getSession()->getCurrentUrl());
    }
  }

}
