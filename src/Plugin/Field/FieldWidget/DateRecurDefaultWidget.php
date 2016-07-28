<?php

namespace Drupal\date_recur\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

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

    $element['rrule'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->rrule) ? $items[$delta]->rrule : NULL,
      '#title' => $this->t('RRULE')
    );

    $element['recur_end_value'] = [
      '#title' => $this->t('Recur event until'),
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => FALSE,
      '#date_date_format' => $this->dateStorage->load('html_date')->getPattern(),
      '#date_date_element' => 'date',
      '#date_time_format' => '',
      '#date_time_element' => 'none',
    ];

    if ($items[$delta]->recur_end_value) {
      $storage_format = $this->fieldDefinition->getSetting('daterange_type_type') == 'date' ? DATETIME_DATE_STORAGE_FORMAT : DATETIME_DATETIME_STORAGE_FORMAT;
      /** @var \Drupal\Core\Datetime\DrupalDateTime $recur_end_date*/
      $recur_end_date = DrupalDateTime::createFromFormat(DATETIME_DATE_STORAGE_FORMAT, $items[$delta]->recur_end_value, DATETIME_STORAGE_TIMEZONE);
      // The date was created and verified during field_load(), so it is safe to
      // use without further inspection.
      datetime_date_default_time($recur_end_date);
      $recur_end_date->setTimezone(new \DateTimeZone($element['recur_end_value']['#date_timezone']));
      $element['recur_end_value']['#default_value'] = $recur_end_date;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    foreach ($values as &$item) {
      if (!empty($item['recur_end_value']) && $item['recur_end_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item['recur_end_value'];
        $format = DATETIME_DATE_STORAGE_FORMAT;
        // Adjust the date for storage.
        $end_date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
        $item['recur_end_value'] = $end_date->format($format);
      }

      // @todo: Why are these needed? DB errors otherwise. Columns seem to be
      // NOT NULL even though they have setRequired(FALSE).
      if (empty($item['recur_end_value'])) {
        $item['recur_end_value'] = '';
      }
      if (empty($item['rrule'])) {
        $item['rrule'] = '';
      }
    }
    return $values;
  }

}
