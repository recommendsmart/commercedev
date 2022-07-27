<?php

namespace Drupal\commerce_avatax;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides an interface for the AvaTax library.
 */
interface AvataxLibInterface {

  /**
   * Creates a new transaction (/api/v2/transactions/create).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The transactions type (e.g SalesOrder|SalesInvoice).
   *
   * @return array
   *   The response array.
   */
  public function transactionsCreate(OrderInterface $order, $type = 'SalesOrder');

  /**
   * Voids a transaction for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function transactionsVoid(OrderInterface $order);

  /**
   * Prepares the transaction request body. (This method should not be public
   * but that makes the tests easier).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The transactions type (e.g SalesOrder|SalesInvoice).
   *
   * @return array
   *   The request parameters array.
   */
  public function prepareTransactionsCreate(OrderInterface $order, $type = 'SalesOrder');

  /**
   * Retrieve geolocation information for a specified address.
   *
   * @param array $address
   *   The address item.
   *
   * @return array
   *   Return AvaTax formatted response.
   */
  public function resolveAddress(array $address);

  /**
   * Validate the give address from Drupal upon AvaTax resolved address.
   *
   * @param array $address
   *   The address item.
   *
   * @return array
   *   Return formatted array of errors and suggestions.
   */
  public function validateAddress(array $address);

}
