<?php

namespace Drupal\date_recur\Plugin\Field\FieldWidget;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'date_recur_interactive_widget' widget.
 *
 * @FieldWidget(
 *   id = "date_recur_interactive_widget",
 *   label = @Translation("Date recur interactive widget"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurInteractiveWidget extends DateRecurDefaultWidget {
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $id = Html::getUniqueId('date-recur');
    $element['value']['#attributes']['data-date-recur-start'] = $id;
    $element['rrule']['#attributes']['data-date-recur-rrule'] = $id;
    $element['#attached']['library'][] = 'date_recur/rrule_widget';

    $element['#pre_render'][] = [get_class($this), 'preRenderFormElement'];

    $element['date_group'] = [
      '#type' => 'container',
      '#weight' => -10,
      '#attributes' => [
        'class' => ['date-recur-container-inline'],
      ],
    ];
    $element['date_group']['seperator'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t('to'),
      '#attributes' => ['class' => ['label', 'seperator']],
      '#weight' => 1,
    ];
//    $element['#title_display'] = 'invisible';
//    $element['#theme_wrappers'] = [];

    return $element;
  }

  public static function preRenderFormElement(array $element) {
    $keys = ['value', 'end_value'];
    $weight = 0;
    foreach ($keys as $key) {
      $element['date_group'][$key] = $element[$key];
      $element['date_group'][$key]['#weight'] = $weight;
      $weight += 2;
      unset($element[$key]);
    }
    return $element;
  }
}

