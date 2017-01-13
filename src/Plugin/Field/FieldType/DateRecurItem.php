<?php

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\date_recur\DateRecurRRule;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

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

  protected $rruleObject;

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
    $properties['timezone'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Timezone'))
      ->setRequired(FALSE);
    $properties['rrule'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('RRule'))
      ->setRequired(FALSE);
    $properties['infinite'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is the RRule an infinite rule?'))
      ->setRequired(FALSE);

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
    $schema['columns']['infinite'] = [
      'description' => 'Infinity of the repeat rule.',
      'type' => 'int',
      'size' => 'tiny',
    ];
    $schema['columns']['timezone'] = [
      'description' => 'The timezone',
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    return $values;
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
  public static function defaultFieldSettings() {
    return [
      'precreate' => 'P2Y',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $options = [];
    for ($i = 1; $i < 6; $i++) {
      $options['P' . $i . 'Y'] = $this->formatPlural($i, '@count year', '@count years', ['@count' => $i]);
    }
    $element['precreate'] = [
      '#type' => 'select',
      '#title' => t('Precreate occurrences'),
      '#description' => t('For infinitely repeating dates, precreate occurrences for this amount of time in the views cache table.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('precreate'),
    ];
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    if ($this->getRRule()) {
      $this->infinite = (int) $this->getRrule()->isInfinite();
    }
    else {
      $this->infinite = 0;
    }
  }

  /**
   * Get the RRule object.
   *
   * @param bool $reset
   * @return bool|\Drupal\date_recur\DateRecurRRule
   */
  public function getRRule($reset = FALSE) {
    if (!$reset && !empty($this->rruleObject)) {
      return $this->rruleObject;
    }
    else {
      $rule = $this->rrule;
      if (empty($rule)) {
        return FALSE;
      }
      $this->rruleObject = new DateRecurRRule($rule, $this->start_date, $this->end_date, $this->timezone);
      return $this->rruleObject;
    }
  }


  /**
   * Get occurrences. Optionally set a start or an end date.
   *
   * @throws \LogicException If the rule is infinite and no $end is supplied.
   *
   * @param null|\DateTime $start
   * @param null|\DateTime $end
   * @return array [[value => DrupalDateTime, end_value => DrupalDateTime], ...]
   */
  public function getOccurrences($start = NULL, $end = NULL) {
    if (empty($this->rrule)) {
      return [];
    }
    return $this->getRRule()->getOccurrencesBetween($start, $end);
  }


  /**
   * Get next occurrences from some date.
   *
   * @param string|\DateTime $start
   * @param int $num
   * @return array
   */
  public function getNextOccurrences($start = 'now', $num = 5) {
    if (empty($this->rrule)) {
      return [];
    }
    if (is_string($start)) {
      $start = new \DateTime($start);
    }
    return $this->getRRule()->getNextOccurrences($start, $num);
  }

  /**
   * Get the occurrences for storage in the cache table (for views).
   *
   * @see DateRecurFieldItemList::postSave()
   *
   * @return array
   */
  public function getOccurrencesForCacheStorage() {
    // Get storage format from settings.
    switch ($this->getSetting('daterange_type')) {
      case DateRangeItem::DATETIME_TYPE_DATE:
        $storageFormat = DATETIME_DATE_STORAGE_FORMAT;
        break;
      default:
        $storageFormat = DATETIME_DATETIME_STORAGE_FORMAT;
        break;
    }

    if (empty($this->rrule)) {
      if (empty($this->end_date)) {
        $this->end_date = $this->start_date;
      }
      return [[
        'value' => DateRecurRRule::massageDateValueForStorage($this->start_date, $storageFormat),
        'end_value' => DateRecurRRule::massageDateValueForStorage($this->end_date, $storageFormat),
      ]];
    }
    else {
      $until = new \DateTime();
      $until->add(new \DateInterval($this->getSetting('precreate')));
      return $this->getRRule()
        ->getOccurrencesForCacheStorage($until, $storageFormat);
    }
  }
}
