<?php

namespace Drupal\animated_scroll_to_top\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure animated scroll to top settings.
 */
class AnimatedScrollToTopForm extends ConfigFormBase {

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return [
      'animated_scroll_to_top.settings',
    ];
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'animated_scroll_to_top_form';
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('animated_scroll_to_top.settings');

    $form['animated_scroll_to_top_position'] = [
      '#title' => $this->t('Button Position'),
      '#description' => $this->t('Animated sroll to top button position'),
      '#type' => 'select',
      '#options' => [
        1 => $this->t('left'),
        2 => $this->t('right'),
      ],
      '#default_value' => $config->get('animated_scroll_to_top_position'),
    ];

    $form['animated_scroll_to_top_button_bg_color'] = [
      '#title' => $this->t('Animated scroll to top button background color'),
      '#description' => $this->t('Animated scroll to top button background color.'),
      '#type' => 'color',
      '#default_value' => $config->get('animated_scroll_to_top_button_bg_color'),
    ];

    $form['animated_scroll_to_top_button_hover_bg_color'] = [
      '#title' => $this->t('Animated scroll to top button hover background color'),
      '#description' => $this->t('Animated scroll to top button hover background color.'),
      '#type' => 'color',
      '#default_value' => $config->get('animated_scroll_to_top_button_hover_bg_color'),
    ];

    $form['animated_scroll_to_top_button_position'] = [
      '#title' => $this->t('Animated scroll to top button position from left/right'),
      '#description' => $this->t('Animated scroll to top button position from left/right.'),
      '#type' => 'number',
      '#default_value' => $config->get('animated_scroll_to_top_button_position'),
    ];

    $form['animated_scroll_to_top_button_height'] = [
      '#title' => $this->t('Animated scroll to top button height'),
      '#description' => $this->t('Animated scroll to top button height.'),
      '#type' => 'number',
      '#default_value' => $config->get('animated_scroll_to_top_button_height'),
    ];

    $form['animated_scroll_to_top_button_width'] = [
      '#title' => $this->t('Animated scroll to top button width'),
      '#description' => $this->t('Animated scroll to top button width.'),
      '#type' => 'number',
      '#default_value' => $config->get('animated_scroll_to_top_button_width'),
    ];

    $form['animated_scroll_to_top_button_bottom'] = [
      '#title' => $this->t('Animated scroll to top button position from bottom'),
      '#description' => $this->t('Animated scroll to top button position from bottom.'),
      '#type' => 'number',
      '#default_value' => $config->get('animated_scroll_to_top_button_bottom'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implement submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('animated_scroll_to_top.settings')
      ->set('animated_scroll_to_top_position', $values['animated_scroll_to_top_position'])
      ->set('animated_scroll_to_top_button_bg_color', $values['animated_scroll_to_top_button_bg_color'])
      ->set('animated_scroll_to_top_button_hover_bg_color', $values['animated_scroll_to_top_button_hover_bg_color'])
      ->set('animated_scroll_to_top_button_height', $values['animated_scroll_to_top_button_height'])
      ->set('animated_scroll_to_top_button_width', $values['animated_scroll_to_top_button_width'])
      ->set('animated_scroll_to_top_button_bottom', $values['animated_scroll_to_top_button_bottom'])
      ->set('animated_scroll_to_top_button_position', $values['animated_scroll_to_top_button_position'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
