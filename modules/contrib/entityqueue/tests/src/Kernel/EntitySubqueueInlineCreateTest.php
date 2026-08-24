<?php

declare(strict_types=1);

namespace Drupal\Tests\entityqueue\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\entityqueue\Form\EntitySubqueueForm;
use Drupal\entityqueue\Form\EntitySubqueueInlineForm;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests creating a subqueue without setting its machine name explicitly.
 *
 * The 'name' base field is the subqueue ID and has no form widget, so any path
 * that doesn't run the form controller (inline entity form, Default Content
 * import, direct API save) used to fail the insert on an empty ID.
 *
 * @see https://www.drupal.org/project/entityqueue/issues/3054945
 *
 * @group entityqueue
 */
#[RunTestsInSeparateProcesses]
class EntitySubqueueInlineCreateTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entityqueue'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_subqueue');
  }

  /**
   * Creates a queue with the 'multiple' (user-managed subqueues) handler.
   */
  protected function createMultipleQueue(): EntityQueue {
    $queue = EntityQueue::create([
      'id' => 'test_queue',
      'label' => 'Test queue',
      'handler' => 'multiple',
      'entity_settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $queue->save();

    return $queue;
  }

  /**
   * The 'name' field has no widget, which is why the machine name is generated.
   *
   * Inline entity form builds the entity solely from its form display, so it
   * cannot populate a field that has no widget there.
   */
  public function testNameFieldMissingFromFormDisplay(): void {
    $queue = $this->createMultipleQueue();
    $subqueue = EntitySubqueue::create(['queue' => $queue->id()]);

    $form_display = EntityFormDisplay::collectRenderDisplay($subqueue, 'default');

    $this->assertArrayHasKey('title', $form_display->getComponents());
    $this->assertArrayNotHasKey('name', $form_display->getComponents());
  }

  /**
   * A subqueue saved without a name gets one generated from its title.
   */
  public function testMachineNameGeneratedFromTitle(): void {
    $queue = $this->createMultipleQueue();
    $subqueue = EntitySubqueue::create([
      'queue' => $queue->id(),
      'title' => 'Featured Articles!',
    ]);
    $subqueue->save();

    $this->assertSame('featured_articles', $subqueue->id());
    $this->assertNotNull(EntitySubqueue::load('featured_articles'));
  }

  /**
   * Automated (simple) subqueues take the queue ID, not a title-derived name.
   */
  public function testAutomatedSubqueueUsesQueueIdAsName(): void {
    $queue = EntityQueue::create([
      'id' => 'simple_q',
      'label' => 'My Simple Queue',
      'handler' => 'simple',
      'entity_settings' => ['target_type' => 'entity_test'],
    ]);
    $queue->save();

    // The 'simple' handler auto-creates the single subqueue, named after the
    // queue.
    $auto = EntitySubqueue::load('simple_q');
    $this->assertNotNull($auto);
    $this->assertSame('simple_q', $auto->id());

    // Saving an automated subqueue with an empty name uses the queue ID, never
    // the title-derived 'my_simple_queue'.
    $auto->delete();
    $rebuilt = EntitySubqueue::create([
      'queue' => 'simple_q',
      'title' => 'My Simple Queue',
    ]);
    $rebuilt->save();
    $this->assertSame('simple_q', $rebuilt->id());
  }

  /**
   * Generated machine names are made unique with a numeric suffix.
   */
  public function testGeneratedMachineNameIsUnique(): void {
    $queue = $this->createMultipleQueue();

    $first = EntitySubqueue::create(['queue' => $queue->id(), 'title' => 'Same title']);
    $first->save();
    $second = EntitySubqueue::create(['queue' => $queue->id(), 'title' => 'Same title']);
    $second->save();

    $this->assertSame('same_title', $first->id());
    $this->assertSame('same_title_2', $second->id());
  }

  /**
   * The reported inline-create path now succeeds instead of failing on 'name'.
   *
   * This mirrors what inline entity form does: extract the form display widget
   * values into the entity (only the title has a widget), then save.
   *
   * @see \Drupal\inline_entity_form\Form\EntityInlineForm::buildEntity()
   */
  public function testInlineStyleCreateSucceeds(): void {
    $queue = $this->createMultipleQueue();
    $subqueue = EntitySubqueue::create(['queue' => $queue->id()]);

    // The 'items' dragtable widget needs AJAX widget state a headless extract
    // can't supply and is irrelevant here, so drop it before extracting.
    $form_display = EntityFormDisplay::collectRenderDisplay($subqueue, 'default');
    $form_display->removeComponent('items');
    $form = ['#parents' => [], '#tree' => TRUE];
    $form_state = new FormState();
    $form_display->buildForm($subqueue, $form, $form_state);
    $form_state->setValues([
      'title' => [['value' => 'My inline subqueue']],
    ]);
    $form_display->extractFormValues($subqueue, $form, $form_state);

    // The form display gives the entity a title but no machine name.
    $this->assertNull($subqueue->id());

    $subqueue->save();
    $this->assertSame('my_inline_subqueue', $subqueue->id());
  }

  /**
   * The inline form's entity builder copies a user-typed machine name.
   */
  public function testInlineFormCopiesTypedMachineName(): void {
    $queue = $this->createMultipleQueue();
    $subqueue = EntitySubqueue::create(['queue' => $queue->id(), 'title' => 'Whatever']);

    $entity_form = ['#parents' => ['sub']];
    $form_state = new FormState();
    $form_state->setValues(['sub' => ['name' => 'custom_name']]);
    EntitySubqueueInlineForm::copyMachineName('entity_subqueue', $subqueue, $entity_form, $form_state);

    $subqueue->save();
    $this->assertSame('custom_name', $subqueue->id());
  }

  /**
   * The shared machine name element is built as an optional, title-sourced one.
   */
  public function testBuildMachineNameElement(): void {
    $queue = $this->createMultipleQueue();
    $subqueue = EntitySubqueue::create(['queue' => $queue->id()]);

    $element = EntitySubqueueForm::buildMachineNameElement($subqueue, \Drupal::service('element_info'));

    $this->assertSame('machine_name', $element['#type']);
    $this->assertSame('title', $element['#source_field']);
    $this->assertFalse($element['#required']);
    // The 'multiple' handler has non-automated subqueues, so the element shows.
    $this->assertTrue($element['#access']);
  }

}
