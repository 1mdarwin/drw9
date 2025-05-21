<?php

namespace Drupal\superfish\Menu;

use Drupal\Core\Url;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

class Manipulator {
  public function transform($tree) {
    foreach ($tree as $key => $item) {
      if ($item->link instanceof MenuLinkContent) {
        $item->link->getEntity()->link->uri = Url::fromRoute('entity.node.canonical', ['node' => 2])->toString();
      }
      $tree[$key] = $item;
//      var_dump($item);
    }
    return $tree;
  }
}
