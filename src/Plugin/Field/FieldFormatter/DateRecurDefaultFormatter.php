<?php

namespace Drupal\date_recur\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
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
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

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
  protected function viewValue(FieldItemInterface $item) {
    $occurrences = $item->getOccurrences();
    foreach ($occurrences as $occurrence) {
      $dates[] = $this->formatDate($occurrence['value']) . ' bis ' . $this->formatDate($occurrence['value2']);
    }
    $build = array(
      '#theme' => 'item_list',
      '#items' => $dates,
    );
    return $build;
  }

}
