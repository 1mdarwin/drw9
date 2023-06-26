<?php

namespace Drupal\blazy;

/**
 * Defines re-usable services and functions for blazy plugins.
 *
 * @todo move some non-media methods into BlazyInterface at 3.x, or before.
 * @todo sub-modules should implement BlazyManagerBaseInterface, not
 * BlazyManagerInterface to have their own unique render methods.
 */
interface BlazyManagerInterface extends BlazyManagerBaseInterface {

  /**
   * Returns the enforced rich media content, or media using theme_blazy().
   *
   * @param array $build
   *   The array containing: item, content, settings, or optional captions.
   * @param int $delta
   *   The optional delta.
   *
   * @return array
   *   The alterable and renderable array of enforced content, or theme_blazy().
   */
  public function getBlazy(array $build, $delta = -1): array;

  /**
   * Builds the Blazy image as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRenderBlazy(array $element): array;

  /**
   * Builds the Blazy outputs as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRenderBuild(array $element): array;

  /**
   * Returns drupalSettings for IO.
   *
   * @param array $attach
   *   The settings which determine what library to attach.
   *
   * @return object
   *   The supported IO drupalSettings.
   */
  public function getIoSettings(array $attach = []): object;

}
