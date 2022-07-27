<?php

/**
 * @file
 * Hooks provided by the Commerce AvaTax module.
 */

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the request body right before its sent to AvaTax.
 *
 * @param array $request_body
 *   The request body array.
 * @param \Drupal\commerce_order\Entity\OrderInterface $order
 *   The order.
 *
 * @see \Drupal\commerce_avatax\Plugin\Commerce\TaxType\Avatax
 */
function hook_commerce_avatax_order_request_alter(array &$request_body, OrderInterface $order) {
  $request_body['type'] = 'SalesInvoice';
}

/**
 * Let other modules respond to the AvaTax response.
 *
 * @param array $response_body
 *   The response body array.
 * @param \Drupal\commerce_order\Entity\OrderInterface $order
 *   The order.
 *
 * @see \Drupal\commerce_avatax\Plugin\Commerce\TaxType\Avatax
 */
function hook_commerce_avatax_order_response_alter(array &$response_body, OrderInterface $order) {
}

/**
 * @} End of "addtogroup hooks".
 */
