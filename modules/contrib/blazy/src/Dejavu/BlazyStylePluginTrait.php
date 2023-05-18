<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\Views\BlazyStylePluginTrait as StylePluginTrait;

/**
 * A Trait common for optional views style plugins.
 *
 * Used by sub-modules.
 *
 * @todo deprecated in blazy:8.x-2.14 and is removed from blazy:8.x-3.0. Use
 *   Drupal\blazy\Views\BlazyStylePluginTrait instead.
 * @see https://www.drupal.org/node/3103018
 */
trait BlazyStylePluginTrait {

  use StylePluginTrait;

}
