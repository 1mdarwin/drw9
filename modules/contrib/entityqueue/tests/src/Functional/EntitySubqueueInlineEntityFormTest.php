<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Functional;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests creating a subqueue inline through an inline entity form widget.
 *
 * @see https://www.drupal.org/project/entityqueue/issues/3054945
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntitySubqueueInlineEntityFormTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'entityqueue', 'inline_entity_form'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    // A queue whose subqueues are user-managed (the 'multiple' handler).
    EntityQueue::create([
      'id' => 'links',
      'label' => 'Links',
      'handler' => 'multiple',
      'entity_settings' => ['target_type' => 'node', 'handler' => 'default:node'],
    ])->save();

    // An entity reference field on the article node type that points at the
    // 'links' subqueue bundle and edits it with the inline entity form widget.
    // A single target bundle is required by the simple widget.
    FieldStorageConfig::create([
      'field_name' => 'field_subqueue',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => ['target_type' => 'entity_subqueue'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_subqueue',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Subqueue',
      'required' => TRUE,
      'settings' => [
        'handler' => 'default:entity_subqueue',
        'handler_settings' => ['target_bundles' => ['links' => 'links']],
      ],
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_subqueue', ['type' => 'inline_entity_form_simple'])
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'administer entityqueue',
      'create article content',
      'access content',
    ]));
  }

  /**
   * The machine name field name of the inline subqueue form.
   */
  protected const NAME_FIELD = 'field_subqueue[0][inline_entity_form][name]';

  /**
   * The title field name of the inline subqueue form.
   */
  protected const TITLE_FIELD = 'field_subqueue[0][inline_entity_form][title][0][value]';

  /**
   * Creating a subqueue inline with no machine name generates one.
   */
  public function testInlineCreateGeneratesMachineName(): void {
    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    // The machine name element is part of the inline form.
    $this->assertSession()->fieldExists(static::NAME_FIELD);

    $this->submitForm([
      'title[0][value]' => 'Host node',
      static::TITLE_FIELD => 'My Inline Subqueue',
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("Field 'name' doesn't have a default value");

    $subqueue = EntitySubqueue::load('my_inline_subqueue');
    $this->assertNotNull($subqueue);
    $this->assertSame('My Inline Subqueue', $subqueue->getTitle());
  }

  /**
   * A machine name typed into the inline form is used as the subqueue ID.
   */
  public function testInlineCreateWithTypedMachineName(): void {
    $this->drupalGet('/node/add/article');
    $this->submitForm([
      'title[0][value]' => 'Host node',
      static::TITLE_FIELD => 'Some title',
      static::NAME_FIELD => 'chosen_name',
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // The typed name wins over the title-derived one.
    $this->assertNull(EntitySubqueue::load('some_title'));
    $subqueue = EntitySubqueue::load('chosen_name');
    $this->assertNotNull($subqueue);
    $this->assertSame('Some title', $subqueue->getTitle());
  }

}
