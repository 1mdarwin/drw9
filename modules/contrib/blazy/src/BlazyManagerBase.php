<?php

namespace Drupal\blazy;

use Drupal\blazy\Cache\BlazyCache;
use Drupal\blazy\Media\BlazyImage;
use Drupal\blazy\Utility\Check;
use Drupal\blazy\Utility\Path;
use Drupal\blazy\Traits\BlazyManagerDeprecatedTrait;

/**
 * Provides common shared methods across Blazy ecosystem to DRY.
 *
 * @todo implements BlazyManagerBaseInterface after sub-modules.
 */
abstract class BlazyManagerBase extends BlazyBase implements BlazyManagerInterface {

  use BlazyManagerDeprecatedTrait;

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $load = [];
    Check::attachments($load, $attach);

    $this->moduleHandler->alter('blazy_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function getIoSettings(array $attach = []): object {
    $io = [];
    $thold = $this->config('io.threshold');
    $thold = str_replace(['[', ']'], '', trim($thold ?: '0'));

    // @todo re-check, looks like the default 0 is broken sometimes.
    if ($thold == '0') {
      $thold = '0, 0.25, 0.5, 0.75, 1';
    }

    $thold = strpos($thold, ',') !== FALSE
      ? array_map('trim', explode(',', $thold)) : [$thold];
    $formatted = [];
    foreach ($thold as $value) {
      $formatted[] = strpos($value, '.') !== FALSE ? (float) $value : (int) $value;
    }

    // Respects hook_blazy_attach_alter() for more fine-grained control.
    foreach (['disconnect', 'rootMargin', 'threshold'] as $key) {
      $default = $key == 'rootMargin' ? '0px' : FALSE;
      $value = $key == 'threshold' ? $formatted : $this->config('io.' . $key);
      $io[$key] = $attach['io.' . $key] ?? ($value ?: $default);
    }

    return (object) $io;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageEffects(): array {
    $cid = 'blazy_image_effects';
    $effects[] = 'blur';
    return $this->getCachedData($cid, $effects);
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes(): array {
    $cid = 'blazy_lightboxes';
    $data = BlazyCache::lightboxes($this->root);
    return $this->getCachedData($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles(): array {
    $styles = [
      'column' => 'CSS3 Columns',
      'grid' => 'Grid Foundation',
      'flex' => 'Flexbox Masonry',
      'nativegrid' => 'Native Grid',
    ];
    $this->moduleHandler->alter('blazy_style', $styles);
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail(array $settings, $item = NULL): array {
    return BlazyImage::thumbnail($settings, $item);
  }

  /**
   * {@inheritdoc}
   */
  public function isBlazy(array &$settings, array $data = []): void {
    Check::blazyOrNot($settings, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData(array &$build, $entity = NULL): void {
    // Do nothing, let extenders share data at ease as needed.
  }

  /**
   * {@inheritdoc}
   */
  public function preSettings(array &$settings): void {
    Blazy::verify($settings);

    $blazies = $settings['blazies'];
    $ui = array_intersect_key($this->config(), BlazyDefault::uiSettings());
    $iframe_domain = $this->config('iframe_domain', 'media.settings');
    $is_debug = !$this->config('css.preprocess', 'system.performance');
    $ui['fx'] = $ui['fx'] ?? '';
    $ui['fx'] = empty($settings['fx']) ? $ui['fx'] : $settings['fx'];
    $ui['blur_minwidth'] = (int) ($ui['blur_minwidth'] ?? 0);
    $fx = $settings['_fx'] ?? $ui['fx'];
    $fx = $blazies->get('fx', $fx);
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $lightboxes = $this->getLightboxes();
    $lightboxes = $blazies->get('lightbox.plugins', $lightboxes) ?: [];
    $is_blur = $fx == 'blur';
    $is_resimage = $this->moduleExists('responsive_image');

    $blazies->set('fx', $fx)
      ->set('iframe_domain', $iframe_domain)
      ->set('is.blur', $is_blur)
      ->set('is.debug', $is_debug)
      ->set('is.resimage', $is_resimage)
      ->set('is.unblazy', $this->config('io.unblazy'))
      ->set('language.current', $language)
      ->set('libs.animate', $fx)
      ->set('libs.blur', $is_blur)
      ->set('lightbox.plugins', $lightboxes)
      ->set('ui', $ui);

    if ($router = Path::routeMatch()) {
      $settings['route_name'] = $route_name = $router->getRouteName();
      $blazies->set('route_name', $route_name);
    }

    // Sub-modules may need to provide their data to be consumed here.
    // Basicaly needs basic UI and definitions above to supply data properly,
    // such as to determine Slick/ Splide own lazy load methods based on UI.
    $this->preSettingsData($settings);

    // Preliminary globals when using the provided API.
    BlazyInternal::preSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function postSettings(array &$settings): void {
    BlazyInternal::postSettings($settings);

    // Sub-modules may need to override Blazy definitions.
    $this->postSettingsData($settings);
  }

  /**
   * Overrides data massaged by [blazy|slick|splide, etc.]_settings_alter().
   */
  public function postSettingsAlter(array &$settings, $entity = NULL): void {
    Check::settingsAlter($settings, $entity);
  }

  /**
   * Provides data to be consumed by Blazy::preSettings().
   *
   * Such as to provide lazy attribute and class for Slick or Splide, etc.
   */
  protected function preSettingsData(array &$settings): void {
    // Do nothing, let extenders input data at ease as needed.
  }

  /**
   * Overrides data massaged by Blazy::postSettings().
   */
  protected function postSettingsData(array &$settings): void {
    // Do nothing, let extenders override data at ease as needed.
  }

  /**
   * Provides attachments and cache common for all blazy-related modules.
   */
  protected function setAttachments(
    array &$element,
    array $settings,
    array $attachments = []
  ): void {
    $cache                = $this->getCacheMetadata($settings);
    $attached             = $this->attach($settings);
    $attachments          = Blazy::merge($attached, $attachments);
    $element['#attached'] = Blazy::merge($attachments, $element, '#attached');
    $element['#cache']    = Blazy::merge($cache, $element, '#cache');
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove at/by 3.x after subs extending BlazyManagerBaseInterface.
   */
  public function build(array $build): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove at/by 3.x after subs extending BlazyManagerBaseInterface.
   */
  public function getBlazy(array $build, $delta = -1): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove at/by 3.x after subs extending BlazyManagerBaseInterface.
   */
  public function preRenderBlazy(array $element): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove at/by 3.x after subs extending BlazyManagerBaseInterface.
   */
  public function preRenderBuild(array $element): array {
    return [];
  }

}
