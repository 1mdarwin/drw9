<?php

/**
 * @file
 * Hooks provided by Superfish.
 */

/**
 * Make menu tree manipulators alterable.
 *
 * @param array &$manipulators
 *   List of manipulator services.
 * @param string $menu_name
 *   Altered menu name (optional).
 * @param array $tree
 *   Menu tree (optional).
 */
function hook_superfish_tree_manipulators_alter(array &$manipulators, $menu_name = NULL, &$tree = []) {
  $manipulators[] = ['callable' => 'mymodule.tree_manipulator:checkAccess'];
}
