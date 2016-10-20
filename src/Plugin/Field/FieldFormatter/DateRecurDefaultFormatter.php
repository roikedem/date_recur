<?php

namespace Drupal\date_recur\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use RRule\RRule;
use When\When;

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
class DateRecurDefaultFormatter extends DateTimeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
      'show_rrule' => TRUE,
      'show_next' => 5,
    ) + parent::defaultSettings();
  }

  protected function showNextOptions() {
    $next_options[-1] = $this->t('All');
    $next_options[0] = $this->t('None');
    for ($i = 1; $i <= 10; $i++) {
      $next_options[$i] = $i;
    }
    return $next_options;
  }

  protected function setTimezone(DrupalDateTime $date) {
    return $date;
  }

  protected function formatDate($date) {
    $format_type = $this->getSetting('format_type');
    $timezone = DATETIME_STORAGE_TIMEZONE;
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
      'show_rrule' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show repeat rule'),
        '#default_value' => $this->getSetting('show_rrule'),
      ],
      'show_next' => [
        '#type' => 'select',
        '#options' => $this->showNextOptions(),
        '#title' => $this->t('Show next repeating dates'),
        '#default_value' => $this->getSetting('show_next'),
      ],
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('show_rrule') ? $this->t('Show repeat rule') : $this->t('Hide repeat rule');
    $summary[] = $this->t('Show next repeating dates: @d', ['@d' => $this->showNextOptions()[$this->getSetting('show_next')]]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(DateRecurItem $item) {
    $dates = [];
    $build = [
      '#theme' => 'date_recur_default_formatter'
    ];

    if ($item->rrule && $this->getSetting('show_rrule')) {
      $build['#repeatrule'] = $item->getRRule()->humanReadable();
    }
    $occurrences = $item->getNextOccurrences('now', $this->getSetting('show_next'));

    foreach ($occurrences as $occurrence) {
      if (!empty($occurrence)) {
        $date = $this->formatDate($occurrence['value']);
        if (!empty($occurrence['end_value']) && $occurrence['value']->format('c') != $occurrence['end_value']->format('c')) {
          // @todo: Make seperator configurable or use a proper placeholdered t string.
          // @todo: Hide end date if identical to start.
          $date .= $this->t(' until ');
          if ($occurrence['end_value']->format('Ymd') == $occurrence['value']->format('Ymd')) {
            $date .= $occurrence['end_value']->format('H:i');

          }
          else {
            $date .= $this->formatDate($occurrence['end_value']);
          }
        }
        $dates[] = $date;
      }
    }
    // @todo: Make this a proper theme hook.
    $build['#occurrences'] = $dates;
    return $build;
  }

}
