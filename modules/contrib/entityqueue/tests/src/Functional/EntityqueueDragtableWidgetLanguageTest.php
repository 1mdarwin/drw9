<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Functional;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the dragtable widget renders items in the interface language.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityqueueDragtableWidgetLanguageTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'entityqueue', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that a queued item's label and edit link follow the active language.
   */
  public function testItemFollowsInterfaceLanguage(): void {
    // Add Spanish and make the interface (and thus content) language follow the
    // path prefix, so the subqueue page can be viewed in either language.
    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'es' => 'es'])
      ->save();
    $this->config('language.types')
      ->set('negotiation.language_interface.enabled', ['language-url' => 0, 'language-selected' => 1])
      ->save();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $node = $this->createNode(['type' => 'article', 'title' => 'First article']);
    $node->addTranslation('es', ['title' => 'Primer artículo'])->save();

    EntityQueue::create([
      'id' => 'q',
      'label' => 'Q',
      'handler' => 'simple',
      'entity_settings' => ['target_type' => 'node', 'handler' => 'default:node'],
      'queue_settings' => ['min_size' => 0, 'max_size' => 0, 'act_as_queue' => FALSE, 'reverse' => FALSE],
    ])->save();
    EntitySubqueue::load('q')->set('items', [$node])->save();

    $this->drupalLogin($this->drupalCreateUser(['administer entityqueue', 'access content']));
    $assert = $this->assertSession();

    // In English the item shows the original label and its edit link points to
    // the untranslated node edit form.
    $this->drupalGet('/en/admin/structure/entityqueue/q/q');
    $assert->pageTextContains('First article');
    $assert->pageTextNotContains('Primer artículo');
    $assert->elementAttributeContains('css', 'a.entityqueue-edit-item-link', 'href', '/en/node/' . $node->id() . '/edit');

    // In Spanish both the label and the edit link follow the interface
    // language rather than always the default language.
    $this->drupalGet('/es/admin/structure/entityqueue/q/q');
    $assert->pageTextContains('Primer artículo');
    $assert->pageTextNotContains('First article');
    $assert->elementAttributeContains('css', 'a.entityqueue-edit-item-link', 'href', '/es/node/' . $node->id() . '/edit');
  }

}
