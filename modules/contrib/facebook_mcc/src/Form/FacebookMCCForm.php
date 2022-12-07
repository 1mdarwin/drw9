<?php

namespace Drupal\facebook_mcc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Utility\Color;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implement config form for Facebook mcc.
 */
class FacebookMCCForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a FacebookMCCForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'facebook_mcc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('facebook_mcc.settings');

    // Load localization list from JSON.
    $local_list = $this->loadLocalList();

    // Form vertical tabs.
    $form['plugin_config_tab'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Facebook MCC Settings'),
    ];

    // Form bases.
    $form['plugin_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Plugin Configurations'),
      '#group' => 'plugin_config_tab',
    ];

    $form['language_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Language Configurations'),
      '#group' => 'plugin_config_tab',
    ];

    // Facebook Page ID Field.
    $form['plugin_config']['facebook_mcc_page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page ID'),
      '#default_value' => $config->get('facebook_mcc_page_id'),
      '#description' => $this->t('Your Facebook page id'),
      '#required' => TRUE,
    ];

    // Facebook App ID Field.
    $form['plugin_config']['facebook_mcc_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $config->get('facebook_mcc_app_id'),
      '#description' => $this->t('Your Facebook app id'),
      '#required' => TRUE,
    ];

    // Facebook SDK Version Field.
    $form['plugin_config']['facebook_mcc_sdk_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook JS SDK Version'),
      '#default_value' => $config->get('facebook_mcc_sdk_version'),
      '#description' => $this->t('Ex: 6.0. You can find the version here: https://developers.facebook.com/docs/javascript/quickstart'),
      '#required' => TRUE,
    ];

    // Facebook MCC Theme Color Field.
    $form['plugin_config']['facebook_mcc_theme_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme Color'),
      '#default_value' => $config->get('facebook_mcc_theme_color'),
      '#description' => $this->t('The color to use as a theme for the plugin. Ex: #3B5998'),
    ];

    // Facebook MCC Logged In Greeting Field.
    $form['plugin_config']['facebook_mcc_logged_in_greeting'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Logged In Greeting'),
      '#rows' => 2,
      '#resizable' => 'none',
      '#maxlength' => 80,
      '#default_value' => $config->get('facebook_mcc_logged_in_greeting'),
      '#description' => $this->t('The greeting text that will be displayed if the user is currently logged in to Facebook. Maximum 80 characters.'),
    ];

    // Facebook MCC Logged Out Greeting Field.
    $form['plugin_config']['facebook_mcc_logged_out_greeting'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Logged Out Greeting'),
      '#rows' => 2,
      '#resizable' => 'none',
      '#maxlength' => 80,
      '#default_value' => $config->get('facebook_mcc_logged_out_greeting'),
      '#description' => $this->t('The greeting text that will be displayed if the user is currently not logged in to Facebook. Maximum 80 characters.'),
    ];

    // Facebook MCC Greeting Dialog Display Field.
    $form['plugin_config']['facebook_mcc_greeting_dialog_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Greeting Dialog Display'),
      '#options' => [
        'default' => $this->t('Default'),
        'show' => $this->t('Show'),
        'hide' => $this->t('Hide'),
        'fade' => $this->t('Fade'),
      ],
      '#default_value' => $config->get('facebook_mcc_greeting_dialog_display'),
      '#description' => $this->t('Sets how the greeting dialog will be displayed.
                                  Default: show on desktop, hide on mobile.
                                  Show: The greeting dialog will always be shown when the plugin loads.
                                  Hide: The greeting dialog of the plugin will always be hidden until a user clicks on the plugin.
                                  Fade: The greeting dialog of the plugin will be shown, then fade away and stay hidden afterwards.
                                  '),

    ];

    // Facebook MCC Logged Out Greeting Field.
    $form['plugin_config']['facebook_mcc_greeting_dialog_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Greeting Dialog Delay'),
      '#min' => 1,
      '#default_value' => $config->get('facebook_mcc_greeting_dialog_delay'),
      '#description' => $this->t('Sets the number of seconds of delay before the greeting dialog is shown after the plugin is loaded.'),
    ];

    // Facebook MCC Language Localization Settings Table.
    $form['language_config']['facebook_mcc_language_map'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Site Language'),
        $this->t('Plugin Display Language'),
      ],
      '#empty' => $this->t('There are no enabled languages.'),
    ];

    // Fill Languages table with enabled languages.
    $language_list = $this->languageManager->getLanguages();
    $language_map = $config->get('facebook_mcc_language_map');

    foreach ($language_list as $language) {
      $form['language_config']['facebook_mcc_language_map'][$language->getId()]['language'] = [
        '#plain_text' => $language->getName(),
      ];
      $form['language_config']['facebook_mcc_language_map'][$language->getId()]['localization'] = [
        $form['language_config']['facebook_mcc_language_map']['local'] = [
          '#type' => 'select',
          '#options' => $local_list,
          '#default_value' => (isset($language_map[$language->getId()])) ? $language_map[$language->getId()]['localization'] : 'en_US',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate Theme Color Field.
    $color = $form_state->getValue('facebook_mcc_theme_color');
    if (!Color::validateHex($color) && strlen($color) != 0) {
      $form_state->setErrorByName('facebook_mcc_theme_color', $this->t('Color must be a 6-digit hexadecimal value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('facebook_mcc.settings');

    $config->set('facebook_mcc_page_id', $form_state->getValue('facebook_mcc_page_id'));
    $config->set('facebook_mcc_app_id', $form_state->getValue('facebook_mcc_app_id'));
    $config->set('facebook_mcc_sdk_version', $form_state->getValue('facebook_mcc_sdk_version'));
    $config->set('facebook_mcc_theme_color', $form_state->getValue('facebook_mcc_theme_color'));
    $config->set('facebook_mcc_logged_in_greeting', $form_state->getValue('facebook_mcc_logged_in_greeting'));
    $config->set('facebook_mcc_logged_out_greeting', $form_state->getValue('facebook_mcc_logged_out_greeting'));
    $config->set('facebook_mcc_greeting_dialog_display', $form_state->getValue('facebook_mcc_greeting_dialog_display'));
    $config->set('facebook_mcc_greeting_dialog_delay', $form_state->getValue('facebook_mcc_greeting_dialog_delay'));
    $config->set('facebook_mcc_language_map', $form_state->getValue('facebook_mcc_language_map'));

    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'facebook_mcc.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function loadLocalList() {
    return Json::decode(file_get_contents(__DIR__ . '/../../locale.json'));
  }

}
