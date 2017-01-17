<?php

namespace Drupal\date_recur\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;

/**
 * Plugin implementation of the 'date_recur_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "date_recur_default_formatter",
 *   label = @Translation("Date recur default formatter"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurDefaultFormatter extends DateRangeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
      'show_rrule' => TRUE,
      'show_next' => 5,
      'occurrence_format_type' => 'medium',
    ) + parent::defaultSettings();
  }

  protected function showNextOptions() {
    // This cannot work for infinite fields.
    // $next_options[-1] = $this->t('All');
    $next_options[0] = $this->t('None');
    for ($i = 1; $i <= 20; $i++) {
      $next_options[$i] = $i;
    }
    return $next_options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['show_rrule'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show repeat rule'),
      '#default_value' => $this->getSetting('show_rrule'),
    ];
    $form['show_next'] = [
      '#type' => 'select',
      '#options' => $this->showNextOptions(),
      '#title' => $this->t('Show next occurrences'),
      '#default_value' => $this->getSetting('show_next'),
    ];

    $form['occurrence_format_type'] = $form['format_type'];
    $form['occurrence_format_type']['#title'] .=  ' ' . t('(Occurrences)');
    $form['occurrence_format_type']['#default_value'] = $this->getSetting('occurrence_format_type');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Show repeat rule') . ': ' . ($this->getSetting('show_rrule') ? $this->t('Yes') : $this->t('No'));
    $summary[] = $this->t('Show next occurrences') . ': ' . $this->showNextOptions()[$this->getSetting('show_next')];
    $date = new DrupalDateTime();
    $date->_dateRecurIsOccurrence = TRUE;
    $summary[] = t('Occurrence format: @display', array('@display' => $this->formatDate($date)));
    return $summary;
  }

  protected function buildDateRangeValue($start_date, $end_date, $isOccurrence = FALSE) {
    if ($isOccurrence) {
      $start_date->_dateRecurIsOccurrence = $end_date->_dateRecurIsOccurrence = TRUE;
    }
    if ($start_date->format('U') !== $end_date->format('U')) {
      $element = [
        'start_date' => $this->buildDateWithIsoAttribute($start_date),
        'separator' => ['#plain_text' => ' ' . $this->getSetting('separator') . ' '],
        'end_date' => $this->buildDateWithIsoAttribute($end_date),
      ];
    }
    else {
      $element = $this->buildDateWithIsoAttribute($start_date);
    }
    return $element;
  }

  protected function formatDate($date) {
    if (empty($date->_dateRecurIsOccurrence)) {
      $format_type = $this->getSetting('format_type');
    }
    else {
      $format_type = $this->getSetting('occurrence_format_type');
    }
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
//    if (empty($GLOBALS['oonce'])) {
//      ksm(debug_backtrace());
//      $GLOBALS['oonce'] = TRUE;
//    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param DateRecurItem $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(DateRecurItem $item) {
//    dsm('view value: ' . $item->start_date->__toString());
//    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
//    ksm($bt);
    $build = [
      '#theme' => 'date_recur_default_formatter'
    ];

    if (empty($item->end_date)) {
      $item->end_date = clone $item->start_date;
    }
    $build['#date'] = $this->buildDateRangeValue($item->start_date, $item->end_date);
    if (!empty($item->rrule)) {
      if ($this->getSetting('show_rrule')) {
        $build['#repeatrule'] = $item->getOccurrenceHandler()->humanReadable();
      }
      $occurrences = $item->getNextOccurrences('now', $this->getSetting('show_next'));
      foreach ($occurrences as $occurrence) {
        if (!empty($occurrence['value'])) {
          $build['#occurrences'][] = $this->buildDateRangeValue(DrupalDateTime::createFromDateTime($occurrence['value']), DrupalDateTime::createFromDateTime($occurrence['end_value']), TRUE);
        }
      }
    }

    if (!empty($item->_attributes)) {
      $build += $item->_attributes;
      // Unset field item attributes since they have been included in the
      // formatter output and should not be rendered in the field template.
      unset($item->_attributes);
    }

    return $build;
  }
}
