<?php

namespace Drupal\block_class\Controller;

use Drupal\block\Entity\Block;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for help routes.
 */
class BlockClassController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The extension list module.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $extensionListModule;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module admin links helper.
   *
   * @var \Drupal\system\ModuleAdminLinksHelper
   */
  protected $moduleAdminLinksHelper;

  /**
   * The user module permissions link helper.
   *
   * @var \Drupal\user\ModulePermissionsLinkHelper
   */
  protected $modulePermissionsLinkHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->extensionListModule = $container->get('extension.list.module');
    $instance->configFactory = $container->get('config.factory');
    $instance->requestStack = $container->get('request_stack');

    // @todo Remove deprecated code when support for core:10.2 is dropped.
    if (!version_compare(\Drupal::VERSION, '10.2', '<')) {
      // For Drupal versions >= 10.2.
      $instance->moduleAdminLinksHelper = $container->get('system.module_admin_links_helper');
      $instance->modulePermissionsLinkHelper = $container->get('user.module_permissions_link_helper');
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $build = [];
    $projectMachineName = 'block_class';

    $projectName = $this->extensionListModule->getName($projectMachineName);

    $build['#title'] = 'Block Class Help';

    $helperMarkup = $this->moduleHandler()->invoke($projectMachineName, 'help', [
      "help.page.$projectMachineName",
      $this->routeMatch,
    ]);

    if (!is_array($helperMarkup)) {
      $helperMarkup = ['#markup' => $helperMarkup];
    }

    $build['top'] = $helperMarkup;

    // Get module's information array.
    $extension_info = $this->extensionListModule->getExtensionInfo($projectMachineName);

    // Only print list of administration pages if the project in question has
    // any such pages associated with it.
    if (version_compare(\Drupal::VERSION, '10.2', '<')) {
      // @phpstan-ignore-next-line
      $admin_tasks = system_get_module_admin_tasks($projectMachineName, $extension_info);
    }
    else {
      $admin_tasks = $this->moduleAdminLinksHelper->getModuleAdminLinks($projectMachineName);
      // Fix compatibility support with versions >= 10.2 which use a string
      // instead of an array for the second argument.
      if ($perms_link = $this->modulePermissionsLinkHelper->getModulePermissionsLink($projectMachineName, $extension_info['name'])) {
        array_push($admin_tasks, $perms_link);
      }
    }

    if (empty($admin_tasks)) {
      return $build;
    }

    // Remove the first menu link item provided by the module which is the
    // parent 'Block Class' link and has the same url path as its first child
    // settings form page.
    array_shift($admin_tasks);

    // Rename some link titles for better clarity.
    if ($admin_tasks[0]['url']->getRouteName() === 'block_class.settings') {
      $admin_tasks[0]['title'] = $this->t('Block Class Settings');
    }
    if ($admin_tasks[1]['url']->getRouteName() === 'block_class.list') {
      $admin_tasks[1]['title'] = $this->t('List Block Classes');
    }

    $build['links'] = [
      '#theme' => 'links__help',
      '#heading' => [
        'level' => 'h3',
        'text' => $this->t('@project_name administration pages', ['@project_name' => $projectName]),
      ],
      '#links' => $admin_tasks,
    ];

    return $build;
  }

  /**
   * Method to show the block list.
   */
  public function blockList() {

    // Get config object.
    $config = $this->configFactory->getEditable('block_class.settings');
    // $form['#attached']['library'][] = 'block_class/block-class';
    $table = '<table>';
    $table .= '<thead>';
    $table .= '<tr>';
    $table .= '<th>' . $this->t('Block') . '</th>';
    $table .= '<th>' . $this->t('Class') . '</th>';
    $table .= '<th>' . $this->t('Attributes') . '</th>';
    $table .= '<th>' . $this->t('Edit') . '</th>';
    $table .= '<th colspan="2" class="block-class-text-center">' . $this->t('Delete') . '</th>';
    $table .= '</tr>';
    $table .= '</thead>';
    $table .= '<tbody>';

    // Load blocks. Todo: We'll implements DI here @codingStandardsIgnoreLine
    $blocks = Block::loadMultiple();

    // Get the quantity of blocks available.
    $qty_blocks = count($blocks);

    // Initial value.
    $page = (int) 1;

    if (!empty($this->requestStack->getCurrentRequest()->query->get('page'))) {
      $page = (int) $this->requestStack->getCurrentRequest()->query->get('page');
    }

    // Get the default items per page. By default is 50.
    $items_per_page = 50;

    // If there is a settings defined with this items get this value there.
    if (!empty($config->get('items_per_page'))) {

      // Update the items per page with the value from settings page.
      $items_per_page = $config->get('items_per_page');
    }

    $from = ($page - 1) * 5;
    $to = ($from + $items_per_page);

    $index = 1;

    foreach ($blocks as $block) {

      if ($index < $from) {
        $index++;
        continue;
      }

      if ($index > $to) {
        break;
      }

      if (empty($block->getThirdPartySetting('block_class', 'classes')) && empty($block->getThirdPartySetting('block_class', 'attributes'))) {
        continue;
      }

      // Get the block classes configured.
      $block_class = $block->getThirdPartySetting('block_class', 'classes');

      // Get the attributes.
      $attributes = $block->getThirdPartySetting('block_class', 'attributes');

      // If classes is empty, and there are attributes but the flag to enable
      // attributes is off, skip.
      if (empty($block_class) && !empty($attributes) && empty($config->get('enable_attributes'))) {
        continue;
      }

      if (empty($attributes)) {
        $attributes = '';
      }

      // Put one attribute per line.
      $attributes = str_replace(PHP_EOL, '<br>', $attributes);

      $table .= '<tr>';
      $table .= '<td><a href="/admin/structure/block/manage/' . $block->id() . '">' . $block->label() . '</a></td>';
      $table .= '<td>' . $block_class . '</td>';
      $table .= '<td>' . $attributes . '</td>';
      $table .= '<td><a href="/admin/structure/block/manage/' . $block->id() . '">' . $this->t('Edit') . '</a></td>';
      $table .= '<td><a href="/admin/config/content/block-class/delete/' . $block->id() . '">' . $this->t('Delete') . '</a></td>';
      $table .= '<td><a href="/admin/config/content/block-class/delete-attribute/' . $block->id() . '">' . $this->t('Delete Attributes') . '</a></td>';
      $table .= '</tr>';

      $index++;
    }

    $table .= '</tbody>';
    $table .= '</table>';

    $markup = $table;

    if ($qty_blocks > $items_per_page) {
      $markup .= '<nav>';
      $markup .= '<ul class="pager__items">';
      $markup .= '<li class="pager__item pager__item--next">';
      $markup .= '<a href="?page=' . ($page + 1) . '">';
      $markup .= '<span>Next ›</span>';
      $markup .= '</a>';
      $markup .= '</li>';
      $markup .= '</ul>';
      $markup .= '</ul>';
    }

    $build = [
      '#markup' => $markup,
      '#attached' => [
        'library' => [
          'block_class/block-class',
        ],
      ],
    ];

    return $build;

  }

  /**
   * Method to show the block list.
   */
  public function classList() {

    $table = '<table>';
    $table .= '<thead>';
    $table .= '<tr>';
    $table .= '<th>' . $this->t('Class') . '</th>';
    $table .= '</tr>';
    $table .= '</thead>';
    $table .= '<tbody>';

    $config = $this->configFactory->getEditable('block_class.settings');
    $block_classes_stored = $config->get('block_classes_stored');

    foreach ($block_classes_stored as $block_class) {

      $table .= '<tr>';
      $table .= '<td>' . $block_class . '</td>';
      $table .= '</tr>';

    }

    $table .= '</tbody>';
    $table .= '</table>';

    $markup = $table;

    $build = [
      '#markup' => $markup,
    ];

    return $build;

  }

  /**
   * Method to show the attribute list.
   */
  public function attributeList() {

    $table = '<table>';
    $table .= '<thead>';
    $table .= '<tr>';
    $table .= '<th>' . $this->t('Attributes') . '</th>';
    $table .= '</tr>';
    $table .= '</thead>';
    $table .= '<tbody>';

    $attributes_inline = [];

    $config = $this->configFactory->getEditable('block_class.settings');

    // Get config object.
    if (!empty($config->get('attributes_inline'))) {
      $attributes_inline = $config->get('attributes_inline');
    }

    // Get the array.
    $attributes_inline = Json::decode($attributes_inline);

    // Get the array values and id in the keys.
    $attributes_inline = array_values($attributes_inline);

    foreach ($attributes_inline as $attribute_inline) {

      $table .= '<tr>';
      $table .= '<td>' . $attribute_inline . '</td>';
      $table .= '</tr>';

    }

    $table .= '</tbody>';
    $table .= '</table>';

    $markup = $table;

    $build = [
      '#markup' => $markup,
    ];

    return $build;

  }

  /**
   * Retrieves suggestions for block class autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param array $stored_items
   *   The stored items for autocompletion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   *
   * @see \Drupal\block\Controller\CategoryAutocompleteController::autocomplete()
   */
  public function handleAutocompleteHelper(Request $request, array $stored_items) {
    $typed_query = $request->query->get('q');
    $matches = [];
    foreach ($stored_items as $stored_item) {
      if (stripos($stored_item, $typed_query) === 0) {
        $matches[] = ['value' => $stored_item, 'label' => Html::escape($stored_item)];
      }
    }
    // Return in JSON Response.
    return new JsonResponse($matches);
  }

  /**
   * Handle Auto Complete.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function handleAutocomplete(Request $request) {
    $block_classes_stored = $this->config('block_class.settings')->get('block_classes_stored');
    return $this->handleAutocompleteHelper($request, $block_classes_stored);
  }

  /**
   * Handle Auto Complete Attributes.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function handleAutocompleteAttributes(Request $request) {
    $attribute_keys_stored = $this->config('block_class.settings')->get('attribute_keys_stored');
    // Get the array.
    $attribute_keys_stored = Json::decode($attribute_keys_stored);
    // Get the array values and id in the keys.
    $attribute_keys_stored = array_values($attribute_keys_stored);

    return $this->handleAutocompleteHelper($request, $attribute_keys_stored);
  }

  /**
   * Handle Auto Complete Attribute Values.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function handleAutocompleteAttributeValues(Request $request) {
    $attribute_value_stored = $this->config('block_class.settings')->get('attribute_value_stored');
    // Get the array.
    $attribute_value_stored = Json::decode($attribute_value_stored);
    // Get the array values and id in the keys.
    $attribute_value_stored = array_values($attribute_value_stored);

    return $this->handleAutocompleteHelper($request, $attribute_value_stored);
  }

}
