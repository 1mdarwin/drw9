<?php

namespace Drupal\blazy\Traits;

use Drupal\blazy\Media\BlazyResponsiveImage;

/**
 * Deprecated methods for easy removal.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
trait BlazyManagerDeprecatedTrait {

  /**
   * Returns the entity repository service.
   *
   * @todo deprecated for BlazyInterface::entityRepository once extended.
   */
  public function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * Returns the entity type manager.
   *
   * @todo deprecated for BlazyInterface::entityTypeManager once extended.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Returns the module handler.
   *
   * @todo deprecated for BlazyInterface::moduleHandler once extended.
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Returns the renderer.
   *
   * @todo deprecated for BlazyInterface::renderer once extended.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Returns the config factory.
   *
   * @todo deprecated for BlazyInterface::configFactory once extended.
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * Returns the cache.
   *
   * @todo deprecated for BlazyInterface::cache once extended.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Returns any config, or keyed by the $setting_name.
   *
   * @todo deprecated for BlazyInterface::config once extended.
   */
  public function configLoad($setting_name = '', $settings = 'blazy.settings') {
    return $this->config($setting_name, $settings);
  }

  /**
   * Returns a shortcut for loading a config entity: image_style, slick, etc.
   *
   * @todo deprecated for BlazyInterface::load once extended.
   */
  public function entityLoad($id, $type = 'image_style') {
    return $this->load($id, $type);
  }

  /**
   * Returns a shortcut for loading multiple configuration entities.
   *
   * @todo deprecated for BlazyInterface::loadMultiple once extended.
   */
  public function entityLoadMultiple($type = 'image_style', $ids = NULL) {
    return $this->loadMultiple($type, $ids);
  }

  /**
   * Collects defined skins as registered via hook_MODULE_NAME_skins_info().
   *
   * @todo remove for sub-modules own skins as plugins at blazy:8.x-2.1+.
   * @see https://www.drupal.org/node/2233261
   * @see https://www.drupal.org/node/3105670
   */
  public function buildSkins($namespace, $skin_class, $methods = []) {
    return [];
  }

  /**
   * Deprecated method, not safe to remove before 3.x for being generic.
   *
   * @deprecated in blazy:8.x-2.5 and is removed from blazy:3.0.0. Use
   *   BlazyResponsiveImage::styles() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public function getResponsiveImageStyles($responsive) {
    return BlazyResponsiveImage::styles($responsive);
  }

}
