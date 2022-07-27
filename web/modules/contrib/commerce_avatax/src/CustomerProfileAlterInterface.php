<?php

namespace Drupal\commerce_avatax;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for altering the customer profile inline form.
 */
interface CustomerProfileAlterInterface {

  /**
   * Gets whether the given customer profile inline form is supported.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if the customer profile inline form is supported, FALSE otherwise.
   */
  public function applies(array &$inline_form, FormStateInterface $form_state);

  /**
   * Alters the inline form to add address validation logic.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alter(array &$inline_form, FormStateInterface $form_state);

}
