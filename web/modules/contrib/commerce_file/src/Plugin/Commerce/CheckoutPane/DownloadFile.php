<?php

namespace Drupal\commerce_file\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the file download checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_file_download",
 *   label = @Translation("Files download"),
 *   default_step = "complete",
 * )
 */
class DownloadFile extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = [];
    // Create an array that will hold all the active file licenses found.
    $license_ids = [];
    // Get all licenced products with file download.
    foreach ($this->order->getItems() as $order_item) {
      if (!$order_item->hasField('license') || $order_item->get('license')->isEmpty()) {
        continue;
      }
      /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
      $license = $order_item->get('license')->entity;
      // Only show download links for activated file licenses.
      if ($license->bundle() !== 'commerce_file' || $license->getState()->getId() !== 'active') {
        continue;
      }
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity->hasField('commerce_file') || $purchased_entity->get('commerce_file')->isEmpty()) {
        continue;
      }
      $license_ids[] = $license->id();
    }

    if ($license_ids) {
      $pane_form['files'] = [
        '#type' => 'view',
        '#name' => 'commerce_file_my_files',
        '#display_id' => 'checkout_complete',
        '#arguments' => [implode('+', $license_ids)],
        '#embed' => TRUE,
      ];
    }

    return $pane_form;
  }

}
