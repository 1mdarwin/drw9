<?php

namespace Drupal\superfish\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Superfish" block.
 *
 * @Block(
 *   id = "superfish",
 *   admin_label = @Translation("Superfish"),
 *   cache = -1,
 *   category = @Translation("Superfish"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class SuperfishBlock extends SystemMenuBlock {

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new SuperfishBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $menu_tree, $menu_active_trail);
    $this->menuActiveTrail = $menu_active_trail;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('module_handler')
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['sf'] = [
      '#type' => 'details',
      '#title' => $this->t('Block settings'),
      '#open' => TRUE,
    ];
    $description = sprintf('<em>(%s: %s)</em>',
      $this->t('Default'),
      $this->t('Horizontal (single row)')
    );
    $form['sf']['superfish_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Menu type'),
      '#description' => $description,
      '#default_value' => $this->configuration['menu_type'],
      '#options' => [
        'horizontal' => $this->t('Horizontal (single row)'),
        'navbar' => $this->t('Horizontal (double row)'),
        'vertical' => $this->t('Vertical (stack)'),
      ],
    ];
    $description = sprintf('<em>(%s: %s)</em>',
      $this->t('Default'),
      $this->t('Default')
    );
    $form['sf']['superfish_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#description' => $description,
      '#default_value' => $this->configuration['style'],
      '#options' => [
        'default' => $this->t('Default'),
        'black' => $this->t('Black'),
        'blue' => $this->t('Blue'),
        'coffee' => $this->t('Coffee'),
        'white' => $this->t('White'),
        'none' => $this->t('None'),
      ],
    ];
    $form['sf']['superfish_arrow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add arrows to parent menus'),
      '#default_value' => $this->configuration['arrow'],
    ];
    $form['sf']['superfish_shadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Drop shadows'),
      '#default_value' => $this->configuration['shadow'],
    ];
    if (count(superfish_effects()) == 4) {
      $easing_instructions = $this->t('jQuery Easing plugin is not installed.');
    }
    else {
      $easing_instructions = $this->t("The plugin provides a handful number of animation effects, they can be used by uploading the 'jquery.easing.js' file to the libraries directory within the 'easing' directory (for example: libraries/easing/jquery.easing.js). Refresh this page after the plugin is uploaded, this will make more effects available in the above list.");
    }
    $description = sprintf('<em>(%s: %s)</em><br>%s<br>',
      $this->t('Default'),
      $this->t('Vertical'),
      $easing_instructions
    );
    $form['sf']['superfish_slide'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide-in effect'),
      '#description' => $description,
      '#default_value' => $this->configuration['slide'],
      '#options' => superfish_effects(),
    ];
    $form['plugins'] = [
      '#type' => 'details',
      '#title' => $this->t('Superfish plugins'),
      '#open' => TRUE,
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Relocates sub-menus when they would otherwise appear outside the browser window area.'),
      $this->t('Default'),
      $this->t('enabled')
    );
    $form['plugins']['superfish_supposition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('jQuery Supposition'),
      '#description' => $description,
      '#default_value' => $this->configuration['supposition'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t("Prevents accidental firing of animations by waiting until the user's mouse slows down enough, hence determining user's <em>intent</em>."),
      $this->t('Default'),
      $this->t('enabled')
    );
    $form['plugins']['superfish_hoverintent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('jQuery hoverIntent'),
      '#description' => $description,
      '#default_value' => $this->configuration['hoverintent'],
    ];
    $description = sprintf('%s <em>(%s)</em>',
      $this->t('<strong>sf-Touchscreen</strong> provides touchscreen compatibility.'),
      $this->t('The first click on a parent hyperlink shows its children and the second click opens the hyperlink.')
    );
    $form['plugins']['sf-touchscreen'] = [
      '#type' => 'details',
      '#title' => $this->t('sf-Touchscreen'),
      '#description' => $description,
      '#open' => FALSE,
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t('Disable'),
      $this->t('Default')
    );
    $form['plugins']['sf-touchscreen']['superfish_touch'] = [
      '#type' => 'radios',
      '#default_value' => $this->configuration['touch'],
      '#options' => [
        0 => $default,
        1 => $this->t('Enable jQuery sf-Touchscreen plugin for this menu.'),
        2 => $this->t("Enable jQuery sf-Touchscreen plugin for this menu depending on the user's Web browser <strong>window width</strong>."),
        3 => $this->t("Enable jQuery sf-Touchscreen plugin for this menu depending on the user's Web browser <strong>user agent</strong>."),
      ],
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t('Hiding the sub-menu on the second tap, adding cloned parent links to the top of sub-menus as well.'),
      $this->t('Default')
    );
    $form['plugins']['sf-touchscreen']['superfish_touchbh'] = [
      '#type' => 'radios',
      '#title' => 'Select a behaviour',
      '#description' => $this->t('Using this plugin, the first click or tap will expand the sub-menu, here you can choose what a second click or tap should do.'),
      '#default_value' => $this->configuration['touchbh'],
      '#options' => [
        0 => $this->t('Opening the parent menu item link on the second tap.'),
        1 => $this->t('Hiding the sub-menu on the second tap.'),
        2 => $default,
      ],
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t('False'),
      $this->t('Default')
    );
    $form['plugins']['sf-touchscreen']['superfish_touchdh'] = [
      '#type' => 'radios',
      '#title' => 'Disable hover',
      '#default_value' => $this->configuration['touchdh'],
      '#options' => [
        0 => $default,
        1 => $this->t('True'),
      ],
    ];
    $description = sprintf('%s<br><br>%s<br><code>&lt;meta name="viewport" content="width=device-width, initial-scale=1.0" /&gt;</code>',
      $this->t("sf-Touchscreen will be enabled only if the width of user's Web browser window is smaller than the below value."),
      $this->t('Please note that in most cases such a meta tag is necessary for this feature to work properly:')
    );
    $form['plugins']['sf-touchscreen']['sf-touchscreen-windowwidth'] = [
      '#type' => 'details',
      '#title' => $this->t('Window width settings'),
      '#description' => $description,
      '#open' => TRUE,
    ];
    $description = sprintf('%s <em>(%s: 768)</em>',
      $this->t('Also known as "Breakpoint".'),
      $this->t('Default')
    );
    $form['plugins']['sf-touchscreen']['sf-touchscreen-windowwidth']['superfish_touchbp'] = [
      '#type' => 'number',
      '#description' => $description,
      '#default_value' => $this->configuration['touchbp'],
      '#field_suffix' => $this->t('pixels'),
      '#size' => 10,
    ];
    $form['plugins']['sf-touchscreen']['sf-touchscreen-useragent'] = [
      '#type' => 'details',
      '#title' => $this->t('User agent settings'),
      '#open' => TRUE,
    ];
    $default = sprintf('%s <em>(%s) (%s)</em>',
      $this->t('Use the pre-defined list of the <strong>user agents</strong>.'),
      $this->t('Default'),
      $this->t('Recommended')
    );
    $form['plugins']['sf-touchscreen']['sf-touchscreen-useragent']['superfish_touchua'] = [
      '#type' => 'radios',
      '#default_value' => $this->configuration['touchua'],
      '#options' => [
        0 => $default,
        1 => $this->t('Use the custom list of the <strong>user agents</strong>.'),
      ],
    ];
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $user_agent = sprintf('<br><strong>%s</strong> %s',
        $this->t('UA string of the current Web browser:'),
        $_SERVER['HTTP_USER_AGENT']
      );
    }
    else {
      $user_agent = '';
    }
    $description = sprintf('%s <em>(%s: %s)</em><br>%s:<ul>
    <li>iPhone*Android*iPad <em><sup>(%s)</sup></em></li>
    <li>Mozilla/5.0 (webOS/1.4.0; U; en-US) AppleWebKit/532.2
    (KHTML, like Gecko) Version/1.0 Safari/532.2 Pre/1.0 * Mozilla/5.0
    (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10
    (KHTML, like Gecko) Mobile/7B405</li>
    </ul>%s',
      $this->t('Could be partial or complete. (Asterisk separated)'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Examples'),
      $this->t('Recommended'),
      $user_agent
    );
    $form['plugins']['sf-touchscreen']['sf-touchscreen-useragent']['superfish_touchual'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom list of the user agents'),
      '#description' => $description,
      '#default_value' => $this->configuration['touchual'],
      '#size' => 100,
      '#maxlength' => 2000,
    ];
    $description = sprintf('<em>(%s: %s)</em>',
      $this->t('Default'),
      $this->t('Client-side (JavaScript)')
    );
    $form['plugins']['sf-touchscreen']['sf-touchscreen-useragent']['superfish_touchuam'] = [
      '#type' => 'select',
      '#title' => $this->t('<strong>User agent</strong> detection method'),
      '#description' => $description,
      '#default_value' => $this->configuration['touchuam'],
      '#options' => [
        0 => $this->t('Client-side (JavaScript)'),
        1 => $this->t('Server-side (PHP)'),
      ],
    ];
    $form['plugins']['sf-smallscreen'] = [
      '#type' => 'details',
      '#title' => $this->t('sf-Smallscreen'),
      '#description' => $this->t('<strong>sf-Smallscreen</strong> provides small-screen compatibility for your menus.'),
      '#open' => FALSE,
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t("Enable jQuery sf-Smallscreen plugin for this menu depending on the user's Web browser <strong>window width</strong>."),
      $this->t('Default')
    );
    $form['plugins']['sf-smallscreen']['superfish_small'] = [
      '#type' => 'radios',
      '#default_value' => $this->configuration['small'],
      '#options' => [
        0 => sprintf('%s.', $this->t('Disable')),
        1 => $this->t('Enable jQuery sf-Smallscreen plugin for this menu.'),
        2 => $default,
        3 => $this->t("Enable jQuery sf-Smallscreen plugin for this menu depending on the user's Web browser <strong>user agent</strong>."),
      ],
    ];
    $description = sprintf('%s<br><br>%s<br><code>&lt;meta name="viewport" content="width=device-width, initial-scale=1.0" /&gt;</code>',
      $this->t("sf-Smallscreen will be enabled only if the width of user's Web browser window is smaller than the below value."),
      $this->t('Please note that in most cases such a meta tag is necessary for this feature to work properly:')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-windowwidth'] = [
      '#type' => 'details',
      '#title' => $this->t('Window width settings'),
      '#description' => $description,
      '#open' => TRUE,
    ];
    $description = sprintf('%s <em>(%s: 768)</em>',
      $this->t('Also known as "Breakpoint".'),
      $this->t('Default')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-windowwidth']['superfish_smallbp'] = [
      '#type' => 'number',
      '#description' => $description,
      '#default_value' => $this->configuration['smallbp'],
      '#field_suffix' => $this->t('pixels'),
      '#size' => 10,
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-useragent'] = [
      '#type' => 'details',
      '#title' => $this->t('User agent settings'),
      '#open' => TRUE,
    ];
    $default = sprintf('%s <em>(%s) (%s)</em>',
      $this->t('Use the pre-defined list of the <strong>user agents</strong>.'),
      $this->t('Default'),
      $this->t('Recommended')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-useragent']['superfish_smallua'] = [
      '#type' => 'radios',
      '#default_value' => $this->configuration['smallua'],
      '#options' => [
        0 => $default,
        1 => $this->t('Use the custom list of the <strong>user agents</strong>.'),
      ],
    ];
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $user_agent = sprintf('<br><strong>%s</strong> %s',
        $this->t('UA string of the current Web browser:'),
        $_SERVER['HTTP_USER_AGENT']
      );
    }
    else {
      $user_agent = '';
    }
    $description = sprintf('%s <em>(%s: %s)</em><br>%s:<ul>
    <li>iPhone*Android*iPad <em><sup>(%s)</sup></em></li>
    <li>Mozilla/5.0 (webOS/1.4.0; U; en-US) AppleWebKit/532.2
    (KHTML, like Gecko) Version/1.0 Safari/532.2 Pre/1.0 * Mozilla/5.0
    (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10
    (KHTML, like Gecko) Mobile/7B405</li>
    </ul>%s',
      $this->t('Could be partial or complete. (Asterisk separated)'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Examples'),
      $this->t('Recommended'),
      $user_agent
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-useragent']['superfish_smallual'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom list of the user agents'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallual'],
      '#size' => 100,
      '#maxlength' => 2000,
    ];
    $description = sprintf('<em>(%s: %s)</em>',
      $this->t('Default'),
      $this->t('Client-side (JavaScript)')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-useragent']['superfish_smalluam'] = [
      '#type' => 'select',
      '#title' => $this->t('<strong>User agent</strong> detection method'),
      '#description' => $description,
      '#default_value' => $this->configuration['smalluam'],
      '#options' => [
        0 => $this->t('Client-side (JavaScript)'),
        1 => $this->t('Server-side (PHP)'),
      ],
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t('Convert the menu to an accordion menu.'),
      $this->t('Default')
    );
    $form['plugins']['sf-smallscreen']['superfish_smallact'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select a type'),
      '#default_value' => $this->configuration['smallact'],
      '#options' => [
        1 => $default,
        0 => $this->t('Convert the menu to a &lt;select&gt; element.'),
      ],
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select'] = [
      '#type' => 'details',
      '#title' => $this->t('&lt;select&gt; settings'),
      '#open' => FALSE,
    ];
    $description = sprintf('%s <em>(%s: %s)</em><br>%s: <em> - %s - </em>',
      $this->t('By default the first item in the &lt;select&gt; element will be the name of the parent menu or the title of this block, you can change this by setting a custom title.'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Example'),
      $this->t('Main Menu')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['superfish_smallset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('&lt;select&gt; title'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallset'],
      '#size' => 50,
      '#maxlength' => 500,
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['superfish_smallasa'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add <em>selected</em> attribute to the &lt;option&gt; element with the class <strong>active</strong> .'),
      '#description' => $this->t('Makes pre-selected the item linked to the active page when the page loads.'),
      '#default_value' => $this->configuration['smallasa'],
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more'] = [
      '#type' => 'details',
      '#title' => $this->t('More'),
      '#open' => FALSE,
    ];
    $title = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Copy the main &lt;ul&gt; classes to the &lt;select&gt;.'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallcmc'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#default_value' => $this->configuration['smallcmc'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Comma separated'),
      $this->t('Default'),
      $this->t('empty')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallecm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these classes from the &lt;select&gt; element'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallecm'],
      '#size' => 100,
      '#maxlength' => 1000,
      '#states' => [
        'enabled' => [
          ':input[name="settings[plugins][sf-smallscreen][sf-smallscreen-select][sf-smallscreen-select-more][superfish_smallcmc]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $title = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Copy the hyperlink classes to the &lt;option&gt; elements of the &lt;select&gt;.'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallchc'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#default_value' => $this->configuration['smallchc'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Comma separated'),
      $this->t('Default'),
      $this->t('empty')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallech'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these classes from the &lt;option&gt; elements of the &lt;select&gt;'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallech'],
      '#size' => 100,
      '#maxlength' => 1000,
      '#states' => [
        'enabled' => [
          ':input[name="settings[plugins][sf-smallscreen][sf-smallscreen-select][sf-smallscreen-select-more][superfish_smallchc]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallicm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include these classes in the &lt;select&gt; element'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallicm'],
      '#size' => 100,
      '#maxlength' => 1000,
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-select']['sf-smallscreen-select-more']['superfish_smallich'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include these classes in the &lt;option&gt; elements of the &lt;select&gt;'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallich'],
      '#size' => 100,
      '#maxlength' => 1000,
    ];
    $form['plugins']['sf-smallscreen']['sf-smallscreen-accordion'] = [
      '#type' => 'details',
      '#title' => $this->t('Accordion settings'),
      '#open' => FALSE,
    ];
    $description = sprintf('%s <em>(%s: %s)</em><br>%s: <em>%s</em>.',
      $this->t('By default the caption of the accordion toggle switch will be the name of the parent menu or the title of this block, you can change this by setting a custom title.'),
      $this->t('Default'),
      $this->t('Menu title'),
      $this->t('Example'),
      $this->t('Menu')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-accordion']['superfish_smallamt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accordion menu title'),
      '#description' => $description,
      '#default_value' => $this->configuration['smallamt'],
      '#size' => 50,
      '#maxlength' => 500,
    ];
    $default = sprintf('%s <em>(%s)</em>',
      $this->t('Use parent menu items as buttons, add cloned parent links to sub-menus as well.'),
      $this->t('Default')
    );
    $form['plugins']['sf-smallscreen']['sf-smallscreen-accordion']['superfish_smallabt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accordion button type'),
      '#default_value' => $this->configuration['smallabt'],
      '#options' => [
        0 => $this->t('Use parent menu items as buttons.'),
        1 => $default,
        2 => $this->t('Create new links next to parent menu item links and use them as buttons.'),
      ],
    ];
    $form['plugins']['sf-supersubs'] = [
      '#type' => 'details',
      '#title' => $this->t('Supersubs'),
      '#description' => $this->t('<strong>Supersubs</strong> makes it possible to define custom widths for your menus.'),
      '#open' => FALSE,
    ];
    $form['plugins']['sf-supersubs']['superfish_supersubs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Supersubs for this menu.'),
      '#default_value' => $this->configuration['supersubs'],
    ];
    $description = sprintf('%s <em>(%s: 12)</em>',
      $this->t('Minimum width for sub-menus, in <strong>em</strong> units.'),
      $this->t('Default')
    );
    $form['plugins']['sf-supersubs']['superfish_minwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum width'),
      '#description' => $description,
      '#default_value' => $this->configuration['minwidth'],
      '#size' => 10,
    ];
    $description = sprintf('%s <em>(%s: 27)</em>',
      $this->t('Maximum width for sub-menus, in <strong>em</strong> units.'),
      $this->t('Default')
    );
    $form['plugins']['sf-supersubs']['superfish_maxwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width'),
      '#description' => $description,
      '#default_value' => $this->configuration['maxwidth'],
      '#size' => 10,
    ];
    $form['sf-multicolumn'] = [
      '#type' => 'details',
      '#description' => $this->t('Please refer to the Superfish module documentation for how you should setup multi-column sub-menus.'),
      '#title' => $this->t('Multi-column sub-menus'),
      '#open' => FALSE,
    ];
    $form['sf-multicolumn']['superfish_multicolumn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable multi-column sub-menus.'),
      '#default_value' => $this->configuration['multicolumn'],
    ];
    $description = sprintf('%s <em>(%s: 1)</em>',
      $this->t('The depth of the first instance of multi-column sub-menus.'),
      $this->t('Default')
    );
    $form['sf-multicolumn']['superfish_multicolumn_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Start from depth'),
      '#description' => $description,
      '#default_value' => $this->configuration['multicolumn_depth'],
      '#options' => array_combine(range(1, 10), range(1, 10)),
    ];
    $description = sprintf('%s <em>(%s: 1)</em>',
      $this->t('The amount of sub-menu levels that will be included in the multi-column sub-menu.'),
      $this->t('Default')
    );
    $form['sf-multicolumn']['superfish_multicolumn_levels'] = [
      '#type' => 'select',
      '#title' => $this->t('Levels'),
      '#description' => $description,
      '#default_value' => $this->configuration['multicolumn_levels'],
      '#options' => array_combine(range(1, 10), range(1, 10)),
    ];
    $form['sf-advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];
    $form['sf-advanced']['sf-settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Superfish'),
      '#open' => FALSE,
    ];
    $description = sprintf('%s <em>(%s: fast)</em>',
      $this->t('The speed of the animation either in <strong>milliseconds</strong> or pre-defined values (<strong>slow, normal, fast</strong>).'),
      $this->t('Default')
    );
    $form['sf-advanced']['sf-settings']['superfish_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation speed'),
      '#description' => $description,
      '#default_value' => $this->configuration['speed'],
      '#size' => 15,
    ];
    $description = sprintf('%s <em>(%s: 800)</em>',
      $this->t('The delay in <strong>milliseconds</strong> that the mouse can remain outside a sub-menu without it closing.'),
      $this->t('Default')
    );
    $form['sf-advanced']['sf-settings']['superfish_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Mouse delay'),
      '#description' => $description,
      '#default_value' => $this->configuration['delay'],
      '#size' => 15,
    ];
    $description = sprintf('%s <em>(%s: 1)</em><br>%s',
      $this->t('The amount of sub-menu levels that remain open or are restored using the ".active-trail" class.'),
      $this->t('Default'),
      $this->t('Change this setting <strong>only and only</strong> if you are <strong>totally sure</strong> of what you are doing.')
    );
    $form['sf-advanced']['sf-settings']['superfish_pathlevels'] = [
      '#type' => 'select',
      '#title' => $this->t('Path levels'),
      '#description' => $description,
      '#default_value' => $this->configuration['pathlevels'],
      '#options' => array_combine(range(0, 10), range(0, 10)),
    ];
    $form['sf-advanced']['sf-hyperlinks'] = [
      '#type' => 'details',
      '#title' => $this->t('Hyperlinks'),
      '#open' => TRUE,
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('By enabling this option, only parent menu items with <em>Expanded</em> option enabled will have their submenus appear.'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['sf-advanced']['sf-hyperlinks']['superfish_expanded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Take "Expanded" option into effect.'),
      '#description' => $description,
      '#default_value' => $this->configuration['expanded'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Add cloned parent links to the top of sub-menus.'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['sf-advanced']['sf-hyperlinks']['superfish_clone_parent'] = [
      '#type' => 'checkbox',
      '#title' => $description,
      '#default_value' => $this->configuration['clone_parent'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Disable hyperlink descriptions ("title" attribute)'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['sf-advanced']['sf-hyperlinks']['superfish_hide_linkdescription'] = [
      '#type' => 'checkbox',
      '#title' => $description,
      '#default_value' => $this->configuration['hide_linkdescription'],
    ];
    $description = sprintf('%s <em>(%s: %s)</em>',
      $this->t('Insert hyperlink descriptions ("title" attribute) into hyperlink texts.'),
      $this->t('Default'),
      $this->t('disabled')
    );
    $form['sf-advanced']['sf-hyperlinks']['superfish_add_linkdescription'] = [
      '#type' => 'checkbox',
      '#title' => $description,
      '#default_value' => $this->configuration['add_linkdescription'],
    ];
    $title = sprintf('%s <em>(sf-depth-1, sf-depth-2, sf-depth-3, ...)</em> <em>(%s: %s)</em>',
      $this->t('Add item depth classes to menu items and their hyperlinks.'),
      $this->t('Default'),
      $this->t('enabled')
    );
    $form['sf-advanced']['sf-hyperlinks']['superfish_itemdepth'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#default_value' => $this->configuration['link_depth_class'],
    ];
    $form['sf-advanced']['sf-hyperlinks']['superfish_link_text_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add prefix to the link text.'),
      '#description' => $this->t('Any text to display before the link text. You may include HTML.'),
      '#default_value' => $this->configuration['link_text_prefix'],
    ];
    $form['sf-advanced']['sf-hyperlinks']['superfish_link_text_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add suffix to the link text.'),
      '#description' => $this->t('Any text to display after the link text. You may include HTML.'),
      '#default_value' => $this->configuration['link_text_suffix'],
    ];
    $form['sf-advanced']['sf-custom-classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom classes'),
      '#open' => TRUE,
    ];
    $description = sprintf('%s <em>(%s: %s)</em><br>%s: top-menu category-science',
      $this->t('(Space separated, without dots)'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Example')
    );
    $form['sf-advanced']['sf-custom-classes']['superfish_ulclass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('For the main UL'),
      '#description' => $description,
      '#default_value' => $this->configuration['custom_list_class'],
      '#size' => 50,
      '#maxlength' => 1000,
    ];
    $description = sprintf('%s <em>(%s: %s)</em><br>%s: science-sub',
      $this->t('(Space separated, without dots)'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Example')
    );
    $form['sf-advanced']['sf-custom-classes']['superfish_liclass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('For the list items'),
      '#description' => $description,
      '#default_value' => $this->configuration['custom_item_class'],
      '#size' => 50,
      '#maxlength' => 1000,
    ];
    $description = sprintf('%s <em>(%s: %s)</em><br>%s: science-link',
      $this->t('(Space separated, without dots)'),
      $this->t('Default'),
      $this->t('empty'),
      $this->t('Example')
    );
    $form['sf-advanced']['sf-custom-classes']['superfish_hlclass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('For the hyperlinks'),
      '#description' => $description,
      '#default_value' => $this->configuration['custom_link_class'],
      '#size' => 50,
      '#maxlength' => 1000,
    ];
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockValidate().
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $touch = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touch',
    ]);
    $touchbp = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-windowwidth',
      'superfish_touchbp',
    ]);
    $touchua = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touchua',
    ]);
    $touchual = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touchual',
    ]);
    $small = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_small',
    ]);
    $smallbp = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-windowwidth',
      'superfish_smallbp',
    ]);
    $smallua = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_smallua',
    ]);
    $smallual = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_smallual',
    ]);
    $minwidth = $form_state->getValue([
      'plugins',
      'sf-supersubs',
      'superfish_minwidth',
    ]);
    $maxwidth = $form_state->getValue([
      'plugins',
      'sf-supersubs',
      'superfish_maxwidth',
    ]);
    $speed = $form_state->getValue([
      'sf-advanced',
      'sf-settings',
      'superfish_speed',
    ]);
    $delay = $form_state->getValue([
      'sf-advanced',
      'sf-settings',
      'superfish_delay',
    ]);

    if (!is_numeric($speed) && !in_array($speed, ['slow', 'normal', 'fast'])) {
      $form_state->setErrorByName('superfish_speed', $this->t('Unacceptable value entered for the "Animation speed" option.'));
    }
    if (!is_numeric($delay)) {
      $form_state->setErrorByName('superfish_delay', $this->t('Unacceptable value entered for the "Mouse delay" option.'));
    }
    if ($touch == 2 && $touchbp == '') {
      $form_state->setErrorByName('superfish_touchbp', $this->t('"sfTouchscreen Breakpoint" option cannot be empty.'));
    }
    if (!is_numeric($touchbp)) {
      $form_state->setErrorByName('superfish_touchbp', $this->t('Unacceptable value enterd for the "sfTouchscreen Breakpoint" option.'));
    }
    if ($touch == 3 && $touchua == 1 && $touchual == '') {
      $form_state->setErrorByName('superfish_touchual', $this->t('List of the touch-screen user agents cannot be empty.'));
    }
    if ($small == 2 && $smallbp == '') {
      $form_state->setErrorByName('superfish_smallbp', $this->t('"sfSmallscreen Breakpoint" option cannot be empty.'));
    }
    if (!is_numeric($smallbp)) {
      $form_state->setErrorByName('superfish_smallbp', $this->t('Unacceptable value entered for the "sfSmallscreen Breakpoint" option.'));
    }
    if ($small == 3 && $smallua == 1 && $smallual == '') {
      $form_state->setErrorByName('superfish_smallual', $this->t('List of the small-screen user agents cannot be empty.'));
    }

    $supersubs_error = FALSE;
    if (!is_numeric($minwidth)) {
      $form_state->setErrorByName('superfish_minwidth', $this->t('Unacceptable value entered for the "Supersubs minimum width" option.'));
      $supersubs_error = TRUE;
    }
    if (!is_numeric($maxwidth)) {
      $form_state->setErrorByName('superfish_maxwidth', $this->t('Unacceptable value entered for the "Supersubs maximum width" option.'));
      $supersubs_error = TRUE;
    }
    if ($supersubs_error !== TRUE && $minwidth > $maxwidth) {
      $form_state->setErrorByName('superfish_maxwidth', $this->t('Supersubs "maximum width" has to be bigger than the "minimum width".'));
    }
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['menu_type'] = $form_state->getValue([
      'sf',
      'superfish_type',
    ]);
    $this->configuration['style'] = $form_state->getValue([
      'sf',
      'superfish_style',
    ]);
    $this->configuration['arrow'] = (int) $form_state->getValue([
      'sf',
      'superfish_arrow',
    ]);
    $this->configuration['shadow'] = (int) $form_state->getValue([
      'sf',
      'superfish_shadow',
    ]);
    $this->configuration['slide'] = $form_state->getValue([
      'sf',
      'superfish_slide',
    ]);

    $this->configuration['supposition'] = (int) $form_state->getValue([
      'plugins',
      'superfish_supposition',
    ]);
    $this->configuration['hoverintent'] = (int) $form_state->getValue([
      'plugins',
      'superfish_hoverintent',
    ]);

    $this->configuration['touch'] = (int) $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'superfish_touch',
    ]);
    $this->configuration['touchbh'] = (int) $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'superfish_touchbh',
    ]);
    $this->configuration['touchdh'] = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'superfish_touchdh',
    ]);
    $this->configuration['touchbp'] = (int) $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-windowwidth',
      'superfish_touchbp',
    ]);
    $this->configuration['touchua'] = (int) $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touchua',
    ]);
    $this->configuration['touchual'] = $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touchual',
    ]);
    $this->configuration['touchuam'] = (int) $form_state->getValue([
      'plugins',
      'sf-touchscreen',
      'sf-touchscreen-useragent',
      'superfish_touchuam',
    ]);

    $this->configuration['small'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'superfish_small',
    ]);
    $this->configuration['smallact'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'superfish_smallact',
    ]);
    $this->configuration['smallbp'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-windowwidth',
      'superfish_smallbp',
    ]);
    $this->configuration['smallua'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_smallua',
    ]);
    $this->configuration['smallual'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_smallual',
    ]);
    $this->configuration['smalluam'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-useragent',
      'superfish_smalluam',
    ]);
    $this->configuration['smallset'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'superfish_smallset',
    ]);
    $this->configuration['smallasa'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'superfish_smallasa',
    ]);
    $this->configuration['smallcmc'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallcmc',
    ]);
    $this->configuration['smallecm'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallecm',
    ]);
    $this->configuration['smallchc'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallchc',
    ]);
    $this->configuration['smallech'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallech',
    ]);
    $this->configuration['smallicm'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallicm',
    ]);
    $this->configuration['smallich'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-select',
      'sf-smallscreen-select-more',
      'superfish_smallich',
    ]);
    $this->configuration['smallamt'] = $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-accordion',
      'superfish_smallamt',
    ]);
    $this->configuration['smallabt'] = (int) $form_state->getValue([
      'plugins',
      'sf-smallscreen',
      'sf-smallscreen-accordion',
      'superfish_smallabt',
    ]);

    $this->configuration['supersubs'] = (int) $form_state->getValue([
      'plugins',
      'sf-supersubs',
      'superfish_supersubs',
    ]);
    $this->configuration['minwidth'] = (int) $form_state->getValue([
      'plugins',
      'sf-supersubs',
      'superfish_minwidth',
    ]);
    $this->configuration['maxwidth'] = (int) $form_state->getValue([
      'plugins',
      'sf-supersubs',
      'superfish_maxwidth',
    ]);
    $this->configuration['multicolumn'] = (int) $form_state->getValue([
      'sf-multicolumn',
      'superfish_multicolumn',
    ]);
    $this->configuration['multicolumn_depth'] = (int) $form_state->getValue([
      'sf-multicolumn',
      'superfish_multicolumn_depth',
    ]);
    $this->configuration['multicolumn_levels'] = (int) $form_state->getValue([
      'sf-multicolumn',
      'superfish_multicolumn_levels',
    ]);

    $this->configuration['speed'] = $form_state->getValue([
      'sf-advanced',
      'sf-settings',
      'superfish_speed',
    ]);
    $this->configuration['delay'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-settings',
      'superfish_delay',
    ]);
    $this->configuration['pathlevels'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-settings',
      'superfish_pathlevels',
    ]);
    $this->configuration['expanded'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_expanded',
    ]);
    $this->configuration['clone_parent'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_clone_parent',
    ]);
    $this->configuration['hide_linkdescription'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_hide_linkdescription',
    ]);
    $this->configuration['add_linkdescription'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_add_linkdescription',
    ]);
    $this->configuration['link_depth_class'] = (int) $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_itemdepth',
    ]);
    $this->configuration['link_text_prefix'] = $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_link_text_prefix',
    ]);
    $this->configuration['link_text_suffix'] = $form_state->getValue([
      'sf-advanced',
      'sf-hyperlinks',
      'superfish_link_text_suffix',
    ]);
    $this->configuration['custom_list_class'] = $form_state->getValue([
      'sf-advanced',
      'sf-custom-classes',
      'superfish_ulclass',
    ]);
    $this->configuration['custom_item_class'] = $form_state->getValue([
      'sf-advanced',
      'sf-custom-classes',
      'superfish_liclass',
    ]);
    $this->configuration['custom_link_class'] = $form_state->getValue([
      'sf-advanced',
      'sf-custom-classes',
      'superfish_hlclass',
    ]);

    if ($this->configuration['style'] == 'none' && empty($this->configuration['smallamt'])) {
      $this->messenger()->addWarning("You have selected the 'None' style option, and the 'Accordion menu title' field is empty. As a result, the accordion buttons will remain invisible unless you apply custom styling");
    }
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {

    $build = [];

    // Menu block ID.
    $menu_name = $this->getDerivativeId();

    // Menu tree.
    $level = $this->configuration['level'];

    // Menu display depth.
    $depth = $this->configuration['depth'];

    /*
     * By not setting any expanded parents we don't limit the loading of the
     * subtrees.
     * Calling MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters we
     * would be doing so.
     * We don't actually need the parents expanded as we do different rendering.
     */
    $maxdepth = NULL;
    if ($depth) {
      $maxdepth = min($level + ($depth - 1), $this->menuTree->maxDepth());
    }

    $parameters = (new MenuTreeParameters())
      ->setMinDepth($level)
      ->setMaxDepth($maxdepth)
      ->setActiveTrail($this->menuActiveTrail->getActiveTrailIds($menu_name));

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return $build;
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    if ($this->moduleHandler->moduleExists('translatable_menu_link_uri')) {
      $manipulators[] = ['callable' => 'superfish.translatable_menu_link_manipulator:transform'];
    }

    // Alter tree manipulators.
    $this->moduleHandler->alter('superfish_tree_manipulators', $manipulators, $menu_name, $tree);

    if ($this->moduleHandler->moduleExists('menu_manipulator')) {
      $manipulators[] = ['callable' => 'menu_manipulator.menu_tree_manipulators:filterTreeByCurrentLanguage'];
    }
    $tree = $this->menuTree->transform($tree, $manipulators);

    // Build the original menu tree to calculate cache tags and contexts.
    $tree_build = $this->menuTree->build($tree);
    $build['#cache'] = $tree_build['#cache'];
    if (empty($tree)) {
      return $build;
    }

    // Block settings which will be passed to the Superfish themes.
    $settings                         = [];
    $settings['expand_all_items']     = $this->configuration['expand_all_items'];
    $settings['level']                = $level;
    $settings['depth']                = $depth;
    $settings['menu_type']            = $this->configuration['menu_type'];
    $settings['style']                = $this->configuration['style'];
    $settings['expanded']             = $this->configuration['expanded'];
    $settings['itemdepth']            = $this->configuration['link_depth_class'];
    $settings['link_text_prefix']     = $this->configuration['link_text_prefix'];
    $settings['link_text_suffix']     = $this->configuration['link_text_suffix'];
    $settings['ulclass']              = $this->configuration['custom_list_class'];
    $settings['liclass']              = $this->configuration['custom_item_class'];
    $settings['hlclass']              = $this->configuration['custom_link_class'];
    $settings['clone_parent']         = $this->configuration['clone_parent'];
    $settings['hide_linkdescription'] = $this->configuration['hide_linkdescription'];
    $settings['add_linkdescription']  = $this->configuration['add_linkdescription'];
    $settings['multicolumn']          = $this->configuration['multicolumn'];
    $settings['multicolumn_depth']    = $this->configuration['menu_type'] == 'navbar' && $this->configuration['multicolumn_depth'] == 1 ? 2 : $this->configuration['multicolumn_depth'];
    $settings['multicolumn_levels']   = $this->configuration['multicolumn_levels'] + $settings['multicolumn_depth'];

    // jQuery plugin options which will be passed to the Drupal behaviour.
    $options = [];
    $options['pathClass'] = $settings['menu_type'] == 'navbar' ? 'active-trail' : '';
    $options['pathLevels'] = $this->configuration['pathlevels'] != 1 ? $this->configuration['pathlevels'] : '';
    $options['delay'] = $this->configuration['delay'] != 800 ? $this->configuration['delay'] : '';
    $options['animation']['opacity'] = 'show';

    $slide = $this->configuration['slide'];
    if (strpos($slide, '_')) {
      $slide = explode('_', $slide);
      switch ($slide[1]) {
        case 'vertical':
          $options['animation']['height'] = ['show', $slide[0]];
          break;

        case 'horizontal':
          $options['animation']['width'] = ['show', $slide[0]];
          break;

        case 'diagonal':
          $options['animation']['height'] = ['show', $slide[0]];
          $options['animation']['width'] = ['show', $slide[0]];
          break;

      }
      $build['#attached']['library'][] = 'superfish/superfish_easing';
    }
    else {
      switch ($slide) {
        case 'vertical':
          $options['animation']['height'] = 'show';
          break;

        case 'horizontal':
          $options['animation']['width'] = 'show';
          break;

        case 'diagonal':
          $options['animation']['height'] = 'show';
          $options['animation']['width'] = 'show';
          break;

      }
    }
    $speed = $this->configuration['speed'];
    if ($speed != 'normal') {
      if (is_numeric($speed)) {
        $options['speed'] = (int) $speed;
      }
      elseif (in_array($speed, ['slow', 'normal', 'fast'])) {
        $options['speed'] = $speed;
      }
    }

    $options['autoArrows'] = $this->configuration['arrow'] == 1;
    $options['dropShadows'] = $this->configuration['shadow'] == 1;

    if ($this->configuration['hoverintent']) {
      $build['#attached']['library'][] = 'superfish/superfish_hoverintent';
    }
    else {
      $options['disableHI'] = TRUE;
    }
    $options = superfish_array_filter($options);

    // Options for Superfish sub-plugins.
    $plugins = [];
    $touchscreen = $this->configuration['touch'];
    if ($touchscreen) {
      $build['#attached']['library'][] = 'superfish/superfish_touchscreen';
      $behaviour = $this->configuration['touchbh'];
      $plugins['touchscreen']['behaviour'] = $behaviour != 2 ? $behaviour : '';
      $plugins['touchscreen']['disableHover'] = $this->configuration['touchdh'];
      $plugins['touchscreen']['cloneParent'] = $this->configuration['clone_parent'];
      switch ($touchscreen) {
        case 1:
          $plugins['touchscreen']['mode'] = 'always_active';
          break;

        case 2:
          $plugins['touchscreen']['mode'] = 'window_width';
          $tsbp = $this->configuration['touchbp'];
          $plugins['touchscreen']['breakpoint'] = $tsbp != 768 ? (float) $tsbp : '';
          break;

        case 3:
          // Which method to use for UA detection.
          $tsuam = $this->configuration['touchuam'];
          $tsua = $this->configuration['touchua'];
          switch ($tsuam) {
            // Client-side.
            case 0:
              switch ($tsua) {
                case 0:
                  $plugins['touchscreen']['mode'] = 'useragent_predefined';
                  break;

                case 1:
                  $plugins['touchscreen']['mode'] = 'useragent_custom';
                  $tsual = mb_strtolower($this->configuration['touchual']);
                  if (strpos($tsual, '*')) {
                    $tsual = str_replace('*', '|', $tsual);
                  }
                  $plugins['touchscreen']['useragent'] = $tsual;
                  break;

              }
              break;

            // Server-side.
            case 1:
              if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $http_user_agent = mb_strtolower($_SERVER['HTTP_USER_AGENT']);
                switch ($tsua) {
                  // Use the pre-defined list of mobile UA strings.
                  case 0:
                    if (preg_match('/(android|bb\d+|meego)|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $http_user_agent)) {
                      $plugins['touchscreen']['mode'] = 'always_active';
                      if ($behaviour == 2) {
                        $settings['clone_parent'] = 1;
                      }
                    }
                    break;

                  // Use the custom list of UA strings.
                  case 1:
                    $tsual = mb_strtolower($this->configuration['touchual']);
                    $tsuac = [];
                    if (strpos($tsual, '*')) {
                      $tsual = explode('*', $tsual);
                      foreach ($tsual as $ua) {
                        $tsuac[] = strpos($http_user_agent, $ua) ? 1 : 0;
                      }
                    }
                    else {
                      $tsuac[] = strpos($http_user_agent, $tsual) ? 1 : 0;
                    }
                    if (in_array(1, $tsuac)) {
                      $plugins['touchscreen']['mode'] = 'always_active';
                      if ($behaviour == 2) {
                        $settings['clone_parent'] = 1;
                      }
                    }
                    break;

                }
              }
              break;

          }
          break;

      }
    }

    $smallscreen = $this->configuration['small'];
    if ($smallscreen) {
      $build['#attached']['library'][] = 'superfish/superfish_smallscreen';
      $plugins['smallscreen']['cloneParent'] = $this->configuration['clone_parent'];
      switch ($smallscreen) {
        case 1:
          $plugins['smallscreen']['mode'] = 'always_active';
          break;

        case 2:
          $plugins['smallscreen']['mode'] = 'window_width';
          $ssbp = $this->configuration['smallbp'];
          if ($ssbp != 768) {
            $plugins['smallscreen']['breakpoint'] = (float) $ssbp;
          }
          else {
            $plugins['smallscreen']['breakpoint'] = '';
          }
          break;

        case 3:
          // Which method to use for UA detection.
          $ssuam = $this->configuration['smalluam'];
          $ssua = $this->configuration['smallua'];
          switch ($ssuam) {
            // Client-side.
            case 0:
              switch ($ssua) {
                case 0:
                  $plugins['smallscreen']['mode'] = 'useragent_predefined';
                  break;

                case 1:
                  $plugins['smallscreen']['mode'] = 'useragent_custom';
                  $ssual = mb_strtolower($this->configuration['smallual']);
                  if (strpos($ssual, '*')) {
                    $ssual = str_replace('*', '|', $ssual);
                  }
                  $plugins['smallscreen']['useragent'] = $ssual;
                  break;

              }
              break;

            // Server-side.
            case 1:
              if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $http_user_agent = mb_strtolower($_SERVER['HTTP_USER_AGENT']);
                switch ($ssua) {
                  // Use the pre-defined list of mobile UA strings.
                  case 0:
                    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $http_user_agent)) {
                      $plugins['smallscreen']['mode'] = 'always_active';
                    }
                    break;

                  // Use the custom list of UA strings.
                  case 1:
                    $ssual = mb_strtolower($this->configuration['smallual']);
                    $ssuac = [];
                    if (strpos($ssual, '*')) {
                      $ssual = explode('*', $ssual);
                      foreach ($ssual as $ua) {
                        $ssuac[] = strpos($http_user_agent, $ua) ? 1 : 0;
                      }
                    }
                    else {
                      $ssuac[] = strpos($http_user_agent, $ssual) ? 1 : 0;
                    }
                    if (in_array(1, $ssuac)) {
                      $plugins['smallscreen']['mode'] = 'always_active';
                    }
                    break;

                }
              }
              break;

          }
          break;

      }
      $type = $this->configuration['smallact'];
      switch ($type) {
        case 0:
          $plugins['smallscreen']['type'] = 'select';
          $plugins['smallscreen']['addSelected'] = $this->configuration['smallasa'] == 1 ? TRUE : '';
          if ($this->configuration['smallcmc'] == 1) {
            $plugins['smallscreen']['menuClasses'] = TRUE;
            $plugins['smallscreen']['excludeClass_menu'] = !empty($this->configuration['smallecm']);
          }

          if ($this->configuration['smallchc'] == 1) {
            $plugins['smallscreen']['hyperlinkClasses'] = TRUE;
            $plugins['smallscreen']['excludeClass_hyperlink'] = !empty($this->configuration['smallech']);
          }
          $plugins['smallscreen']['includeClass_menu'] = !empty($this->configuration['smallicm']);
          $plugins['smallscreen']['includeClass_hyperlink'] = !empty($this->configuration['smallich']);
          break;

        case 1:
          $ab = $this->configuration['smallabt'];
          $plugins['smallscreen']['accordionButton'] = $ab != 1 ? $ab : '';
          if ($this->t('Expand') != 'Expand') {
            $plugins['smallscreen']['expandText'] = $this->t('Expand');
          }
          if ($this->t('Collapse') != 'Collapse') {
            $plugins['smallscreen']['collapseText'] = $this->t('Collapse');
          }
          break;

      }
    }

    if ($this->configuration['supposition']) {
      $plugins['supposition'] = TRUE;
      $build['#attached']['library'][] = 'superfish/superfish_supposition';
    }

    if ($this->configuration['supersubs']) {
      $build['#attached']['library'][] = 'superfish/superfish_supersubs';
      $minwidth = $this->configuration['minwidth'];
      $maxwidth = $this->configuration['maxwidth'];
      $plugins['supersubs']['minWidth'] = $minwidth != 12 ? $minwidth : '';
      $plugins['supersubs']['maxWidth'] = $maxwidth != 27 ? $maxwidth : '';
      if (empty($plugins['supersubs']['minWidth']) && empty($plugins['supersubs']['maxWidth'])) {
        $plugins['supersubs'] = TRUE;
      }
    }

    // Attaching the required JavaScript and CSS files.
    $build['#attached']['library'][] = 'superfish/superfish';
    if ($settings['style'] != 'none') {
      $style = 'superfish/superfish_style_' . $settings['style'];
      $build['#attached']['library'][] = $style;
    }

    // Title for the small-screen menu.
    if ($smallscreen) {
      $title = '';
      switch ($type) {
        case 0:
          $title = $this->configuration['smallset'] ?? $this->label();
          break;

        case 1:
          $title = $this->configuration['smallamt'] ?? '';
          break;

      }
      $plugins['smallscreen']['title'] = $title;
    }

    // Unique HTML ID.
    $id = Html::getUniqueId('superfish-' . $menu_name);

    // Preparing the Drupal behaviour.
    $build['#attached']['drupalSettings']['superfish'][$id]['id'] = $id;
    $build['#attached']['drupalSettings']['superfish'][$id]['sf'] = $options ?? [];

    $plugins = superfish_array_filter($plugins);
    if (!empty($plugins)) {
      $build['#attached']['drupalSettings']['superfish'][$id]['plugins'] = $plugins;
    }

    // Calling the theme.
    $build['content'] = [
      '#theme'  => 'superfish',
      '#menu_name' => $menu_name,
      '#html_id' => $id,
      '#tree' => $tree,
      '#settings' => $settings,
    ];

    return $build;
  }

  /**
   * Overrides \Drupal\block\BlockBase::defaultConfiguration().
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'level' => 1,
      'depth' => 0,
      'menu_type' => 'horizontal',
      'style' => 'default',
      'arrow' => 1,
      'shadow' => 1,
      'speed' => 'fast',
      'delay' => 800,
      'slide' => 'vertical',
      'supposition' => 1,
      'hoverintent' => 1,
      'touch' => 0,
      'touchbh' => 2,
      'touchdh' => 0,
      'touchbp' => 768,
      'touchua' => 0,
      'touchual' => '',
      'touchuam' => 0,
      'small' => 2,
      'smallbp' => 768,
      'smallua' => 0,
      'smallual' => '',
      'smalluam' => 0,
      'smallact' => 1,
      'smallset' => '',
      'smallasa' => 0,
      'smallcmc' => 0,
      'smallecm' => '',
      'smallchc' => 0,
      'smallech' => '',
      'smallicm' => '',
      'smallich' => '',
      'smallamt' => $this->getPluginDefinition()['admin_label'],
      'smallabt' => 1,
      'supersubs' => 1,
      'minwidth' => 12,
      'maxwidth' => 27,
      'multicolumn' => 0,
      'multicolumn_depth' => 1,
      'multicolumn_levels' => 0,
      'pathlevels' => 1,
      'expanded' => 0,
      'clone_parent' => 0,
      'hide_linkdescription' => 0,
      'add_linkdescription' => 0,
      'link_depth_class' => 1,
      'link_text_prefix' => '',
      'link_text_suffix' => '',
      'custom_list_class' => '',
      'custom_item_class' => '',
      'custom_link_class' => '',
    ];
  }

}
