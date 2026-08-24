<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the post-update that converts the relationship 'limit_queue' to a list.
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntityQueuePostUpdateTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'entityqueue'];

  /**
   * The legacy scalar value is invalid under the new schema, so seed it raw.
   *
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The post-update converts a scalar 'limit_queue' to a list.
   */
  public function testLimitQueueConvertedToList(): void {
    View::create([
      'id' => 'eq_legacy',
      'label' => 'Legacy',
      'base_table' => 'node_field_data',
      'base_field' => 'nid',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Default',
          'position' => 0,
          'display_options' => [
            'relationships' => [
              // Pre-1.11: a single queue ID stored as a scalar.
              'scalar' => [
                'id' => 'scalar',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue',
                'relationship' => 'none',
                'limit_queue' => 'my_queue',
              ],
              // Saved through the 1.11 UI: a checkboxes-style array keyed by
              // queue ID, with unchecked boxes left as a falsy value.
              'keyed' => [
                'id' => 'keyed',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue',
                'relationship' => 'none',
                'limit_queue' => ['queue_a' => 'queue_a', 'queue_b' => 0],
              ],
              // Already a list: left untouched.
              'list' => [
                'id' => 'list',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue',
                'relationship' => 'none',
                'limit_queue' => ['queue_c'],
              ],
            ],
            'filters' => [
              // An old in-queue filter that still carries the long-removed
              // 'limit_queue' option under an early-Drupal-8 'options' wrapper.
              // The wrapper is orphaned and must be dropped.
              'stale' => [
                'id' => 'stale',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue_in_queue',
                'relationship' => 'none',
                'options' => ['limit_queue' => 'my_queue'],
              ],
              // An 'options' wrapper holding more than 'limit_queue' is left
              // alone, so nothing unexpected is discarded.
              'keep' => [
                'id' => 'keep',
                'table' => 'node_field_data',
                'field' => 'entityqueue_relationship',
                'plugin_id' => 'entity_queue_in_queue',
                'relationship' => 'none',
                'options' => ['limit_queue' => 'my_queue', 'extra' => 'x'],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    \Drupal::moduleHandler()->loadInclude('entityqueue', 'php', 'entityqueue.post_update');
    $sandbox = [];
    entityqueue_post_update_relationship_limit_queue_list($sandbox);

    $display_options = View::load('eq_legacy')->get('display')['default']['display_options'];
    $relationships = $display_options['relationships'];
    $this->assertSame(['my_queue'], $relationships['scalar']['limit_queue']);
    $this->assertSame(['queue_a'], $relationships['keyed']['limit_queue']);
    $this->assertSame(['queue_c'], $relationships['list']['limit_queue']);

    $this->assertArrayNotHasKey('options', $display_options['filters']['stale']);
    $this->assertSame(['limit_queue' => 'my_queue', 'extra' => 'x'], $display_options['filters']['keep']['options']);
  }

  /**
   * The post-update backfills the dragtable widget's publish-status setting.
   */
  public function testAddPublishStatusWidgetSetting(): void {
    // A form display whose dragtable widget predates the setting: seed it
    // without the key. No field definition exists for 'field_eq', so the
    // display stores the settings verbatim rather than filling in defaults.
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'field_eq' => [
          'type' => 'entityqueue_dragtable',
          'region' => 'content',
          'weight' => 0,
          'settings' => [
            'link_to_entity' => FALSE,
            'link_to_edit_form' => TRUE,
          ],
          'third_party_settings' => [],
        ],
      ],
    ])->save();

    $component = EntityFormDisplay::load('entity_test.entity_test.default')->getComponent('field_eq');
    $this->assertArrayNotHasKey('show_publish_status', $component['settings']);

    \Drupal::moduleHandler()->loadInclude('entityqueue', 'php', 'entityqueue.post_update');
    $sandbox = [];
    entityqueue_post_update_add_publish_status_widget_setting($sandbox);

    $component = EntityFormDisplay::load('entity_test.entity_test.default')->getComponent('field_eq');
    $this->assertSame('unpublished', $component['settings']['show_publish_status']);
  }

}
