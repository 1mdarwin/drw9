<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\Views\BlazyStylePluginBase as StylePluginBase;

/**
 * A base for blazy views integration to have re-usable methods in one place.
 *
 * Used by sub-modules.
 *
 * @todo deprecated in blazy:8.x-2.14 and is removed from blazy:8.x-3.0. Use
 *   Drupal\blazy\Views\BlazyStylePluginBase instead.
 * @see https://www.drupal.org/node/3103018
 */
abstract class BlazyStylePluginBase extends StylePluginBase {}
