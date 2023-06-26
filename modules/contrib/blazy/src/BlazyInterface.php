<?php

namespace Drupal\blazy;

/**
 * Provides common blazy utility methods.
 */
interface BlazyInterface {

  /**
   * Defines constant placeholder Data URI image.
   *
   * @todo deprecated and removed for Placeholder::DATA anytime.
   */
  const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * Returns the app root.
   *
   * @return \SplString
   *   The app root.
   */
  public function root();

  /**
   * Returns the entity repository service.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  public function entityRepository();

  /**
   * Returns the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function entityTypeManager();

  /**
   * Returns the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function moduleHandler();

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function renderer();

  /**
   * Returns the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function configFactory();

  /**
   * Returns the cache service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The app root.
   */
  public function cache();

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManager
   *   The language manager.
   */
  public function languageManager();

  /**
   * Returns any config, or keyed by the $setting_name.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = NULL, $group = 'blazy.settings');

  /**
   * Returns the entity query object for this entity type.
   *
   * @param string $type
   *   The entity type.
   * @param string $conjunction
   *   The operator for the query.
   *
   * @return object
   *   The entity query object.
   */
  public function entityQuery($type, $conjunction = 'AND');

  /**
   * Returns a shortcut for entity type storage.
   *
   * @param string $type
   *   The entity type.
   *
   * @return object
   *   The entity type storage object.
   */
  public function getStorage($type = 'media');

  /**
   * Returns a shortcut for loading an entity: image_style, slick, etc.
   *
   * @param string $id
   *   The entity ID.
   * @param string $type
   *   The entity type.
   *
   * @return mixed|object
   *   The entity, or empty.
   */
  public function load($id, $type = 'image_style');

  /**
   * Returns a shortcut for loading multiple entities.
   *
   * @param string $type
   *   The entity type.
   * @param array|string $ids
   *   The entity ID(s) as fiters.
   *
   * @return array
   *   The entities, or empty array.
   */
  public function loadMultiple($type = 'image_style', $ids = NULL);

  /**
   * Returns a shortcut for loading entity by its properties.
   *
   * The only difference from EntityStorageBase::loadByProperties() is the
   * explicit access TRUE specific for content entities, FALSE config ones.
   *
   * @see https://www.drupal.org/node/3201242
   */
  public function loadByProperties(
    array $values,
    $type = 'file',
    $access = TRUE,
    $conjunction = 'AND',
    $condition = 'IN'
  ): array;

  /**
   * Returns a shortcut for loading entity by its UUID.
   *
   * @param string $uuid
   *   The entity UUID.
   * @param string $type
   *   The entity type.
   *
   * @return mixed|object
   *   The entity, else NULL.
   */
  public function loadByUuid($uuid, $type = 'file');

  /**
   * Returns cached data identified by its cache ID, normally alterable data.
   *
   * @param string $cid
   *   The cache ID, als used for the hook_alter.
   * @param array $data
   *   The given data to cache.
   * @param bool $reset
   *   Whether to re-fetch in case not cached yet.
   * @param string $alter
   *   The specific alter for the hook_alter, otherwise $cid.
   * @param array $context
   *   The optional context or info for the hook_alter.
   *
   * @return array
   *   The cache data.
   */
  public function getCachedData(
    $cid,
    array $data = [],
    $reset = FALSE,
    $alter = NULL,
    array $context = []
  ): array;

  /**
   * Returns the cache metadata common for all blazy-related modules.
   *
   * @param array $build
   *   The provided build info.
   *
   * @return array
   *   The cache metadata.
   */
  public function getCacheMetadata(array $build = []);

  /**
   * Alias for Blazy::getLibrariesPath() to get libraries path.
   *
   * @param string $name
   *   The library name.
   * @param bool $base_path
   *   Whether to prefix it with an a base path, deprecated.
   *
   * @return string
   *   The path to library or NULL if not found.
   */
  public function getLibrariesPath($name, $base_path = FALSE): ?string;

  /**
   * Alias for Blazy::getPath() to get module or theme path.
   *
   * @param string $type
   *   The object type, can be module or theme.
   * @param string $name
   *   The object name.
   * @param bool $absolute
   *   Whether to return an absolute path.
   *
   * @return string
   *   The path to object or NULL if not found.
   */
  public function getPath($type, $name, $absolute = FALSE): ?string;

  /**
   * A wrapper for \Drupal\Core\Extension\ModuleHandlerInterface::moduleExists.
   *
   * @param string $name
   *   The module name.
   *
   * @return bool
   *   Whether the module exists, or not.
   */
  public function moduleExists($name): bool;

  /**
   * Returns items wrapped by theme_item_list(), can be a grid, or plain list.
   *
   * Alias for Blazy::grid() for sub-modules and easy organization later.
   *
   * @param array $items
   *   The grid items.
   * @param array $settings
   *   The given settings.
   *
   * @return array
   *   The modified array of grid items.
   */
  public function toGrid(array $items, array $settings): array;

}
