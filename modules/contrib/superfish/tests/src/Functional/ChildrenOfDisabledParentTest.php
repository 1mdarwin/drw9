<?php

namespace Drupal\Tests\superfish\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that children of disabled menu links are not rendered.
 *
 * @group superfish
 */
class ChildrenOfDisabledParentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'menu_link_content',
    'superfish',
  ];

  /**
   * The name of the menu used in the test.
   */
  protected string $menuName = 'tools';

  /**
   * Tests that children of a disabled parent link are not rendered.
   */
  public function testChildrenOfDisabledParentNotRendered(): void {
    $this->drupalPlaceBlock('superfish:' . $this->menuName, [
      'expand_all_items' => TRUE,
      'depth' => 0,
    ]);

    $parent = $this->createMenuLink('Parent link', TRUE);
    $this->createMenuLink('Child one', TRUE, $parent->getPluginId());
    $this->createMenuLink('Child two', TRUE, $parent->getPluginId());
    $this->createMenuLink('Top level link');

    // All links are rendered while the parent is enabled.
    $this->drupalGet('<front>');
    $this->assertSession()->linkExists('Parent link');
    $this->assertSession()->linkExists('Child one');
    $this->assertSession()->linkExists('Child two');
    $this->assertSession()->linkExists('Top level link');

    // Disable the parent link.
    $parent->set('enabled', 0);
    $parent->save();

    // The disabled parent and its children must not be rendered.
    $this->drupalGet('<front>');
    $this->assertSession()->linkNotExists('Parent link');
    $this->assertSession()->linkNotExists('Child one');
    $this->assertSession()->linkNotExists('Child two');
    $this->assertSession()->linkExists('Top level link');
  }

  /**
   * Creates a menu link in the test menu.
   *
   * @param string $title
   *   The link title.
   * @param bool $enabled
   *   Whether the link is enabled.
   * @param string|null $parent
   *   The plugin ID of the parent link, or NULL for a top-level link.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   The created menu link.
   */
  protected function createMenuLink(string $title, bool $enabled = TRUE, ?string $parent = NULL): MenuLinkContent {
    $link = MenuLinkContent::create([
      'title' => $title,
      'link' => [['uri' => 'internal:/']],
      'menu_name' => $this->menuName,
      'enabled' => $enabled,
      'expanded' => TRUE,
    ]);
    if ($parent) {
      $link->set('parent', $parent);
    }
    $link->save();
    return $link;
  }

}
