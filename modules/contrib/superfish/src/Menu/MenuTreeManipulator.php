<?php

namespace Drupal\superfish\Menu;

/**
 * Superfish menu tree manipulator.
 */
class MenuTreeManipulator {

  /**
   * Removes disabled links and their descendants from the menu tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The filtered menu tree.
   */
  public function filterDisabledLinks(array $tree) {
    foreach ($tree as $key => $element) {
      $definition = $element->link->getPluginDefinition();
      if (isset($definition['enabled']) && !$definition['enabled']) {
        unset($tree[$key]);
      }
      else {
        $element->subtree = $this->filterDisabledLinks($element->subtree);
      }
    }
    return $tree;
  }

}
