<?php

namespace Drupal\date_recur\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'date_recur_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "date_recur_default_widget",
 *   label = @Translation("Date recur default widget"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurDefaultWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    foreach (array('value', 'end_value') as $key) {
      $element[$key]['#date_timezone'] = DATETIME_STORAGE_TIMEZONE;
    }

    $element['end_value']['#required'] = FALSE;

    $element['rrule'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->rrule) ? $items[$delta]->rrule : NULL,
      '#title' => $this->t('RRULE')
    );

    $element['recur_end_value'] = [
      '#title' => $this->t('Recur event until'),
      '#required' => FALSE,
      '#date_time_format' => '',
      '#date_time_element' => 'none',
    ] + $element['value'];

    if ($items[$delta]->recur_end_value) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $recur_end_value = $items[$delta]->recur_end_value;
      $element['recur_end_value']['#default_value'] = $this->createDefaultValue($recur_end_value, $element['recur_end_value']['#date_timezone']);
    }

    return $element;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This overrides DateRangeWidgetBase to remove timezone handling.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   * @param string $timezone
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  protected function createDefaultValue($date, $timezone) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      // A date without time will pick up the current time, use the default
      // time.
      datetime_date_default_time($date);
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as &$item) {
      if (!empty($item['recur_end_value']) && $item['recur_end_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item['recur_end_value'];
        $item['recur_end_value'] = $end_date->format(DATETIME_DATE_STORAGE_FORMAT);
      }
      if (empty($item['end_value'])) {
        $item['end_value'] = $item['value'];
      }
      if (empty($item['rrule'])) {
        $item['rrule'] = '';
      }
    }
    return $values;
  }

}
