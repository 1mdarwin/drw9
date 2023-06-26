<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\blazy\Views\BlazyStylePluginBase;
use Drupal\slick\SlickDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class common for Slick style plugins.
 */
abstract class SlickViewsBase extends BlazyStylePluginBase {

  /**
   * The slick service manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->manager = $container->get('slick.manager');
    $instance->blazyManager = $instance->blazyManager ?? $container->get('blazy.manager');

    return $instance;
  }

  /**
   * Returns the slick manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the slick admin.
   */
  public function admin() {
    return \Drupal::service('slick.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (SlickDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Returns the defined scopes for the current form.
   */
  protected function getDefinedFormScopes(array $extra_fields = []) {
    // Pass the common field options relevant to this style.
    $fields = [
      'captions',
      'classes',
      'images',
      'layouts',
      'links',
      'overlays',
      'thumbnails',
      'thumb_captions',
      'titles',
    ];

    // Fetches the returned field definitions to be used to define form scopes.
    $fields = array_merge($fields, $extra_fields);
    $definition = $this->getDefinedFieldOptions($fields);

    // @todo remove _form for forms when Blazy 2.x has it.
    $options = [
      'fieldable_form',
      'grid_form',
      'id',
      'nav',
      'style',
      'thumb_positions',
      'vanilla',
    ];

    foreach ($options as $key) {
      $definition[$key] = TRUE;
    }

    $definition['forms'] = ['fieldable' => TRUE, 'grid' => TRUE];
    $definition['opening_class'] = 'form--views';
    $definition['_views'] = TRUE;

    return $definition;
  }

  /**
   * Build the Slick settings form.
   */
  protected function buildSettingsForm(&$form, &$definition) {
    $count = count($definition['captions']);
    $definition['captions_count'] = $count;

    $this->admin()->buildSettingsForm($form, $definition);

    // @todo remove custom classes for Blazy 2.x and 1.x updates.
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;
    $title = '<p class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise slick markups apply which require some fields added below.</small>');
    $title .= '</p>';

    $form['opening']['#markup'] = '<div class="form--slick form--views form--half form--vanilla has-tooltip' . $wide . '">' . $title;

    if (isset($form['image'])) {
      $form['image']['#description'] .= ' ' . $this->t('Use Blazy formatter to have it lazyloaded. Other supported Formatters: Colorbox, Intense, Responsive image, Video Embed Field, Youtube Field.');
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#description'] .= ' ' . $this->t('Be sure to CHECK "<strong>Style settings > Use field template</strong>" _only if using Slick formatter for nested sliders, otherwise keep it UNCHECKED!');
    }

    // Bring in dots thumbnail effect normally used by Slick Image formatter.
    $form['thumbnail_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Dots thumbnail effect'),
      '#options' => [
        'hover' => $this->t('Hoverable'),
        'grid' => $this->t('Static grid'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Dependent on a Skin, Dots and Thumbnail image options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.'),
      '#weight' => -100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettings() {
    // @todo move it into self::prepareSettings() post blazy:2.x.
    $this->options['item_id'] = 'slide';
    $this->options['namespace'] = 'slick';
    $settings = parent::buildSettings();
    $blazies = $settings['blazies'] ?? NULL;

    // Prepare needed settings to work with.
    $settings['caption'] = array_filter($settings['caption']);
    $nav = !$settings['vanilla'] && $settings['optionset_thumbnail'] && isset($this->view->result[1]);
    $settings['overridables'] = empty($settings['override']) ? array_filter($settings['overridables']) : $settings['overridables'];

    // BC for non-required Display style. Blazy 2.5+ requires explicit style.
    if (!empty($settings['grid'])
      && !empty($settings['visible_items'])
      && empty($settings['style'])) {
      $settings['style'] = 'grid';
    }

    // @todo remove settings post Blazy 2.10.
    $settings['nav'] = $nav;
    if ($blazies) {
      $blazies->set('is.nav', $nav);
    }

    return $settings;
  }

  /**
   * Returns slick contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $blazies = $settings['blazies'] ?? NULL;
    $item_id = 'slide';
    $nav     = !empty($sets['nav']);

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $slide = [];
      $thumb = $slide[$item_id] = [];
      $sets  = $settings;

      // @todo remove all checks post Blazy 2.10.
      if (method_exists($this, 'reset')) {
        $this->reset($sets);
      }

      if (isset($sets['blazies'])) {
        $blazies = $sets['blazies'];
        $blazies->set('is.reset', TRUE);
      }

      // Provides a potential unique thumbnail different from the main image.
      if (!empty($sets['thumbnail'])) {
        $tn = $this->getFieldRenderable($row, 0, $sets['thumbnail']);
        if (isset($tn['rendered']['#image_style'])) {
          if ($item = $tn['rendered']['#item'] ?? NULL) {
            $uri = (($entity = $item->entity) && empty($item->uri)) ? $entity->getFileUri() : $item->uri;
            $sets['thumbnail_style'] = $tn_style = $tn['rendered']['#image_style'];
            $tn_uri = empty($tn_style)
              ? $uri
              : $this->manager
                ->entityLoad($tn_style, 'image_style')
                ->buildUri($uri);

            if ($tn_uri) {
              $sets['thumbnail_uri'] = $tn_uri;

              if ($blazies) {
                $blazies->set('thumbnail.uri', $tn_uri);
              }
            }
          }
        }
      }

      $slide['settings'] = $sets;

      // Use Vanilla slick if so configured, ignoring Slick markups.
      if (!empty($sets['vanilla'])) {
        $slide[$item_id] = $view->rowPlugin->render($row);
      }
      else {
        // Otherwise, extra works. With a working Views cache, no big deal.
        $this->buildElement($slide, $row, $index);

        // Build thumbnail navs if so configured.
        if ($nav) {
          $thumb[$item_id] = empty($sets['thumbnail']) ? []
            : $this->getFieldRendered($index, $sets['thumbnail']);

          $thumb['caption'] = empty($sets['thumbnail_caption']) ? []
            : $this->getFieldRendered($index, $sets['thumbnail_caption']);

          $build['thumb']['items'][$index] = $thumb;
        }
      }

      if (!empty($sets['class'])) {
        $classes = $this->getFieldString($row, $sets['class'], $index);
        $slide['settings']['class'] = empty($classes[$index]) ? [] : $classes[$index];
      }

      if (empty($slide[$item_id]) && !empty($sets['image'])) {
        $slide[$item_id] = $this->getFieldRendered($index, $sets['image']);
      }

      $build['items'][$index] = $slide;
      unset($slide, $thumb);
    }

    unset($view->row_index);
    return $build;
  }

  /**
   * Check Blazy formatter to build lightbox galleries.
   */
  protected function checkBlazy(array &$settings, array $build, array $rows = []) {
    // Extracts Blazy formatter settings if available.
    // @todo re-check and remove, first.data already takes care of this.
    if (empty($settings['vanilla']) && isset($build['items'][0])) {
      $this->blazyManager()->isBlazy($settings, $build['items'][0]);
    }
    // @todo remove check Blazy 2.10.
    $blazies = $settings['blazies'] ?? NULL;
    if ($data = $this->getFirstImage($rows[0] ?? NULL)) {
      if ($blazies) {
        $blazies->set('first.data', $data);
      }
      // @todo remove post Blazy 2.10.
      else {
        $settings['first_image'] = $data;
      }
    }
  }

}
