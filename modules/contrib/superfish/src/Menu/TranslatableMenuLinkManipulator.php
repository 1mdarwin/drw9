<?php

namespace Drupal\superfish\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\link\LinkItemInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Translatable Menu Link Manipulator class.
 */
class TranslatableMenuLinkManipulator {

  /**
   * The static menu link service used to store updates to weight/parent etc.
   *
   * @var \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  protected $staticOverride;

  /**
   * Constructs a new Manipulator.
   *
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   */
  public function __construct(StaticMenuLinkOverridesInterface $static_override) {
    $this->staticOverride = $static_override;
  }

  /**
   * Transform menu link to translation versions.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $tree
   *   Menu tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement
   *   Transformed menu tree.
   */
  public function transform($tree) {
    foreach ($tree as $key => $item) {
      $tree[$key] = $this->transformItem($item);
    }
    return $tree;
  }

  /**
   * Transform menu item.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $menu_item
   *   Menu item.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement
   *   Menu item.
   */
  public function transformItem(MenuLinkTreeElement $menu_item) {
    foreach ($menu_item->subtree as $key => $subitem) {
      $menu_item->subtree[$key] = $this->overrideLink($subitem);
    }
    return $this->overrideLink($menu_item);
  }

  /**
   * Override menu item.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $menu_item
   *   Menu item.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement
   *   Menu item.
   */
  protected function overrideLink(MenuLinkTreeElement $menu_item) {
    if ($menu_item->link instanceof MenuLinkContent) {
      $overridden_url = $menu_item->link->getEntity()->link_override->first();
      if ($overridden_url instanceof LinkItemInterface && !$overridden_url->isEmpty()) {
        $url = $overridden_url->getUrl();
        $plugin_definition = $menu_item->link->getPluginDefinition();
        if ($url->isRouted()) {
          $plugin_definition['route_name'] = $url->getRouteName();
          $plugin_definition['route_parameters'] = $url->getRouteParameters();
          $plugin_definition['url'] = NULL;
          $plugin_definition['options'] = [];
        }
        else {
          $plugin_definition['route_name'] = NULL;
          $plugin_definition['route_parameters'] = [];
          $plugin_definition['url'] = $url->getUri();
          $plugin_definition['options'] = $menu_item->link->getUrlObject()->getOptions() + $url->getOptions();
        }

        $menu_item->link = new MenuLinkDefault([], $menu_item->link->getPluginId(), $plugin_definition, $this->staticOverride);
      }
    }

    return $menu_item;
  }

}
