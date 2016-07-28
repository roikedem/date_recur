<?php

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\Plugin\Field\FieldType\DateRangeItem;
use RRule\RRule;

/**
 * Plugin implementation of the 'date_recur' field type.
 *
 * @FieldType(
 *   id = "date_recur",
 *   label = @Translation("Date Recur"),
 *   description = @Translation("Recurring dates field"),
 *   default_widget = "date_recur_default_widget",
 *   default_formatter = "date_recur_default_formatter",
 *   list_class = "\Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList"
 * )
 */
class DateRecurItem extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['rrule'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('RRule'))
      ->setRequired(FALSE);
    $properties['recur_end_value'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('End date value'))
      ->setRequired(FALSE);
    $properties['recur_end_date'] = DataDefinition::create('any')
      ->setLabel(t('Computed recur_end date'))
      ->setDescription(t('The computed recur_end DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'recur_end_value')
      ->setSetting('date type', 'date');

    return $properties;
  }




  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['rrule'] = [
      'description' => 'The repeat rule.',
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['columns']['recur_end_value'] = [
      'description' => 'When to stop recurring',
      'type' => 'varchar',
      'length' => 20,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $values['recur_end_value'] = $values['end_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return parent::isEmpty();
  }

  public function onChange($property_name, $notify = TRUE) {
    if ($property_name == 'recur_end_value') {
      $this->recur_end_date = NULL;
    }
    parent::onChange($property_name, $notify);
  }

  public function getRRule() {
    $rule = $this->rrule;
    $rule .= ';DTSTART=' . $this->start_date->format('Y-m-d');
    return new RRule($rule);

  }

  /**
   * @param DrupalDateTime $start
   * @param DrupalDateTime $end
   * @return DrupalDateTime[]
   * @throws \Exception
   */
  protected function createOccurrences($start = NULL, $end = NULL, $timezoneDisplay = FALSE) {
    $dates[] = ['value' => $this->start_date, 'value2' => $this->end_date];
    if (empty($this->rrule)) {
      return $dates;
    }
    if (empty($start)) {
      $start = $this->start_date;
    }
    if (empty($end)) {
      $end = $this->recur_end_value;
    }
    if (empty($end)) {
      $end = clone $start;
      // @todo: Make this configurable.
      $end->add(new \DateInterval('P1Y'));
    }

    if (!empty($this->end_date)) {
      /** @var \DateInterval $diff */
      $diff = $this->start_date->diff($this->end_date);
    }

    $time = $this->start_date->format('H:i');
    $rrule = $this->getRRule();

    /** @var \DateTime[] $occurrences */
    $occurrences = $rrule->getOccurrencesBetween($start, $end);
    foreach ($occurrences as $occurrence) {
      $date = DrupalDateTime::createFromFormat('Ymd H:i', $occurrence->format('Ymd') . ' ' . $time, $start->getTimezone());
      if ($timezoneDisplay) {
        $date->setTimeZone(timezone_open(drupal_get_user_timezone()));
      }
      else {
        $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
      }
      $date_end = clone $date;
      if (!empty($diff)) {
        $date_end = $date_end->add($diff);
      }
      $dates[] = ['value' => $date, 'value2' => $date_end];
    }
    return $dates;
  }

  public function getOccurrences($start = NULL, $end = NULL) {
    return $this->createOccurrences($start, $end, TRUE);
  }

  public function getOccurrencesForStorage() {
    $occurrences = $this->createOccurrences(NULL, NULL, FALSE);
    foreach ($occurrences as &$row) {
      foreach ($row as $key => $date) {
        $row[$key] = $this->massageDateValueForStorage($date);
      }
    }
    return $occurrences;
  }

  /**
   * @param DrupalDateTime $date
   * @return string
   */
  protected function massageDateValueForStorage($date) {
    switch ($this->getSetting('daterange_type')) {
      case DateRangeItem::DATERANGE_TYPE_DATE:
        // If this is a date-only field, set it to the default time so the
        // timezone conversion can be reversed.
        datetime_date_default_time($date);
        $format = DATETIME_DATE_STORAGE_FORMAT;
        break;

      default:
        $format = DATETIME_DATETIME_STORAGE_FORMAT;
        break;
    }
    // Adjust the date for storage.
    $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    return $date->format($format);
  }
}
