<?php

namespace Drupal\blazy\Views;

/**
 * Provides base views style plugin interface.
 */
interface BlazyStyleBaseInterface {

  /**
   * Returns the blazy manager.
   */
  public function blazyManager();

  /**
   * Returns the first Blazy formatter found, to save image dimensions once.
   *
   * Given 100 images on a page, Blazy will call
   * ImageStyle::transformDimensions() once rather than 100 times and let the
   * 100 images inherit it as long as the image style has CROP in the name.
   */
  public function getFirstImage($row): array;

  /**
   * Returns the renderable array of field containing rendered and raw data.
   */
  public function getFieldRenderable($row, $index, $field_name = '', $multiple = FALSE): array;

}
