<?php

namespace Drupal\blazy\Views;

use Drupal\Component\Utility\Xss;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\blazy\BlazyInternal;

/**
 * A Trait common for optional views style plugins.
 */
trait BlazyStylePluginTrait {

  /**
   * {@inheritdoc}
   */
  public function getImageRenderable(array &$settings, $row, $index): array {
    $blazies = $settings['blazies'];
    $image = $this->getImageArray($row, $index, $settings['image']);
    $rendered = $image['rendered'] ?? [];

    // Supports 'group_rows' option.
    // @todo recheck if any side issues for not having raw key.
    if (!$rendered) {
      return $image;
    }

    // If the image has #item property, lazyload may work, otherwise skip.
    // This hustle is to lazyload tons of images -- grids, large galleries,
    // gridstack, mason, with multimedia/ lightboxes for free.
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    if ($item = $this->getImageItem($image)) {
      // Supports multiple image styles within a single view such as GridStack,
      // else fallbacks to the defined image style if available.
      if (empty($settings['image_style'])) {
        $settings['image_style'] = $rendered['#image_style'] ?? '';
      }

      // Converts image formatter for blazy to reduce complexity with CSS
      // background option, and other options, and still lazyload it.
      $theme = $rendered['#theme']
        ?? $rendered['#build'][0]['#theme']
        ?? '';

      if ($theme && in_array($theme, ['blazy', 'image_formatter'])) {
        if ($theme == 'blazy') {
          // Pass Blazy field formatter settings into Views style plugin.
          // This allows richer contents such as multimedia/ lightbox for free.
          // Yet, ensures the Views style plugin wins over Blazy formatter,
          // such as with GridStack which may have its own breakpoints.
          $blazy_settings = array_filter($rendered['#build']['settings']);
          $settings = array_merge($blazy_settings, array_filter($settings));

          // Reserves crucial blazy specific settings.
          BlazyInternal::preserve($settings, $blazy_settings);

          $settings['blazies'] = $blazy_settings['blazies'];
        }
        elseif ($theme == 'image_formatter') {
          // Deals with "link to content/image" by formatters.
          $url = $rendered['#url'] ?? '';
          $blazies->set('entity.url', $url);

          // Prevent images from having absurd height when being lazyloaded.
          // Allows to disable it by _noratio such as enforced CSS background.
          $noratio = $settings['_noratio'] ?? '';
          $settings['ratio'] = $blazies->get('is.noratio', $noratio) ? '' : 'fluid';
          if (empty($settings['media_switch']) && $url) {
            $settings['media_switch'] = 'content';
          }
        }

        // Rebuilds the image for the brand new richer Blazy.
        // With the working Views cache, nothing to worry much.
        $build = ['item' => $item, 'settings' => $settings];
        $image['rendered'] = $this->blazyManager->getBlazy($build, $index);
      }
    }

    return $image;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageArray($row, $index, $field_image = ''): array {
    if (!empty($field_image)
      && $image = $this->getFieldRenderable($row, $index, $field_image)) {

      // Known image formatters: Blazy, Image, etc. which provides ImageItem.
      // Else dump Video embed thumbnail/video/colorbox as is.
      if ($this->getImageItem($image) || isset($image['rendered'])) {
        return $image;
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getImageItem($image): ?ImageItem {
    $item = NULL;

    if ($rendered = ($image['rendered'] ?? [])) {
      // Image formatter.
      $item = $rendered['#item'] ?? NULL;

      // Blazy formatter, also supports multiple, `group_rows`.
      if ($build = ($rendered['#build'] ?? [])) {
        $item = $build['item'] ?? $build[0]['#item'] ?? $item;
      }
    }

    // Don't know other reasonable formatters to work with.
    return is_object($item) ? $item : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCaption($index, array $settings = []): array {
    $items = [];
    $keys = array_keys($this->view->field);

    if (!empty($settings['caption'])) {
      // Exclude non-caption fields so that theme_views_view_fields() kicks in
      // and only render expected caption fields. As long as not-hidden, each
      // caption field should be wrapped with Views markups.
      $excludes = array_diff_assoc(array_combine($keys, $keys), $settings['caption']);
      foreach ($excludes as $field) {
        $this->view->field[$field]->options['exclude'] = TRUE;
      }

      $items['data'] = $this->view->rowPlugin->render($this->view->result[$index]);
    }

    $items['link'] = empty($settings['link']) ? []
      : $this->getFieldRendered($index, $settings['link']);

    $items['title'] = empty($settings['title']) ? []
      : $this->getFieldRendered($index, $settings['title'], TRUE);

    $items['overlay'] = empty($settings['overlay']) ? []
      : $this->getFieldRendered($index, $settings['overlay']);

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayout(array &$settings, $index): void {
    $layout = $settings['layout'] ?? '';
    if (strpos($layout, 'field_') !== FALSE) {
      $settings['layout'] = strip_tags($this->getField($index, $layout));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldRendered($index, $field_name = '', $restricted = FALSE): array {
    if (!empty($field_name) && $output = $this->getField($index, $field_name)) {
      return is_array($output) ? $output : [
        '#markup' => ($restricted ? Xss::filterAdmin($output) : $output),
      ];
    }
    return [];
  }

}
