<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests basic functionality of date_recur fields.
 *
 * @group date_recur
 */
class DateRecurTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * Tests adding a field, setting values, reading occurrences.
   */
  public function testGetOccurrencesForDisplay() {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'abc',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
        'occurrence_handler_plugin' => 'date_recur_occurrence_handler',
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'abc',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    $entity = EntityTest::create();
    $entity->abc = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ];

    // No need to save the entity.
    $this->assertTrue($entity->isNew());
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    $item = $entity->abc;
    $occurrences = $item[0]->getOccurrenceHandler()
      ->getOccurrencesForDisplay(NULL, NULL, 2);
    $this->assertEquals('Tue, 17 Jun 2014 09:00:00 +1000', $occurrences[0]['value']->format('r'));
    $this->assertEquals('Tue, 17 Jun 2014 17:00:00 +1000', $occurrences[0]['end_value']->format('r'));
    $this->assertEquals('Wed, 18 Jun 2014 09:00:00 +1000', $occurrences[1]['value']->format('r'));
    $this->assertEquals('Wed, 18 Jun 2014 17:00:00 +1000', $occurrences[1]['end_value']->format('r'));
  }

}
