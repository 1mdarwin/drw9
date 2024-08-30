<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Manager\Generator;
use Drupal\simple_sitemap\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Simple XML Sitemap forms.
 */
abstract class SimpleSitemapFormBase extends ConfigFormBase {

  /**
   * The sitemap generator service.
   *
   * @var \Drupal\simple_sitemap\Manager\Generator
   */
  protected $generator;

  /**
   * The simple_sitemap.settings service.
   *
   * @var \Drupal\simple_sitemap\Settings
   */
  protected $settings;

  /**
   * Helper class for working with forms.
   *
   * @var \Drupal\simple_sitemap\Form\FormHelper
   */
  protected $formHelper;

  /**
   * SimpleSitemapFormBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The sitemap generator service.
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   Helper class for working with forms.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    Generator $generator,
    Settings $settings,
    FormHelper $form_helper,
  ) {
    $this->generator = $generator;
    $this->settings = $settings;
    $this->formHelper = $form_helper;

    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.settings'),
      $container->get('simple_sitemap.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['simple_sitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->formHelper->regenerateNowFormSubmit($form, $form_state);
  }

}
