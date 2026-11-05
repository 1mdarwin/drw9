<?php

namespace Drupal\Tests\profile\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the profile hook implementations not covered elsewhere.
 *
 * @group profile
 */
#[RunTestsInSeparateProcesses]
class ProfileHooksTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'field_ui',
    'profile',
    'views',
  ];

  /**
   * The profile type.
   *
   * @var \Drupal\profile\Entity\ProfileTypeInterface
   */
  protected $profileType;

  /**
   * The private field.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('view');
    $this->installConfig(['profile', 'user']);

    $this->profileType = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
      'multiple' => TRUE,
      'allow_revisions' => TRUE,
    ]);
    $this->profileType->save();

    FieldStorageConfig::create([
      'field_name' => 'field_private',
      'entity_type' => 'profile',
      'type' => 'string',
    ])->save();
    $this->field = FieldConfig::create([
      'field_name' => 'field_private',
      'entity_type' => 'profile',
      'bundle' => 'test',
    ]);
    $this->field->setThirdPartySetting('profile', 'profile_private', TRUE);
    $this->field->save();
  }

  /**
   * Tests that the profile type flags are added to the bundle info.
   */
  public function testBundleInfo(): void {
    $bundle_info = $this->container->get('entity_type.bundle.info')->getBundleInfo('profile');
    $this->assertTrue($bundle_info['test']['multiple']);
    $this->assertTrue($bundle_info['test']['revision_ui']);
  }

  /**
   * Tests that private fields can't be used in JSON:API filters.
   */
  public function testJsonapiFilterAccess(): void {
    $module_handler = $this->container->get('module_handler');
    $anonymous = new AnonymousUserSession();
    $access = $module_handler->invokeAll('jsonapi_entity_field_filter_access', [$this->field, $anonymous]);
    $this->assertTrue(reset($access)->isForbidden());

    $admin = $this->createUser(['administer profile']);
    $access = $module_handler->invokeAll('jsonapi_entity_field_filter_access', [$this->field, $admin]);
    $this->assertTrue(reset($access)->isNeutral());
  }

  /**
   * Tests the private field setting on the field config edit form.
   */
  public function testFieldConfigEditForm(): void {
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('field_config', 'edit')
      ->setEntity($this->field);
    $form_state = (new FormState())->setFormObject($form_object);
    $form = ['actions' => ['submit' => ['#submit' => []]]];
    $form_id = 'field_config_edit_form';
    $this->container->get('module_handler')->alter(['form', 'form_field_config_edit_form'], $form, $form_state, $form_id);
    $this->assertTrue($form['field']['profile']['profile_private']['#default_value']);

    // Unchecking the setting removes it from the field.
    $form_state->setValues(['profile_private' => 0]);
    $submit = end($form['actions']['submit']['#submit']);
    call_user_func($submit, $form, $form_state);
    $this->assertNull(FieldConfig::load('profile.test.field_private')->getThirdPartySetting('profile', 'profile_private'));
  }

  /**
   * Tests the profile theme suggestions.
   */
  public function testThemeSuggestions(): void {
    $profile = Profile::create([
      'type' => 'test',
      'uid' => $this->createUser()->id(),
    ]);
    $profile->save();

    $variables = [
      'theme_hook_original' => 'profile',
      'elements' => [
        '#profile' => $profile,
        '#view_mode' => 'full',
      ],
    ];
    $suggestions = $this->container->get('module_handler')->invokeAll('theme_suggestions_profile', [$variables]);
    $this->assertSame([
      'profile',
      'profile__full',
      'profile__test',
      'profile__test__full',
      'profile__' . $profile->id(),
      'profile__' . $profile->id() . '__full',
    ], $suggestions);
  }

}
