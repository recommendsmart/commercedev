<?php

namespace Drupal\commerce_shipping\Plugin\Commerce\Condition;

use CommerceGuys\Addressing\Zone\Zone;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the shipping address condition for shipments.
 *
 * @CommerceCondition(
 *   id = "shipment_address_order",
 *   label = @Translation("Shipping address"),
 *   category = @Translation("Shipping"),
 *   entity_type = "commerce_order",
 *   weight = 10,
 * )
 */
class ShipmentAddressOrder extends ShipmentAddress {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $entity;
    if ($order->get('shipments')->isEmpty()) {
      return FALSE;
    }
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $order->get('shipments')->first()->entity;
    $shipping_profile = $shipment->getShippingProfile();
    if (!$shipping_profile) {
      return FALSE;
    }
    $address = $shipping_profile->get('address')->first();
    if (!$address) {
      // The conditions can't be applied until the shipping address is known.
      return FALSE;
    }
    $zone = new Zone([
      'id' => 'shipping',
      'label' => 'N/A',
    ] + $this->configuration['zone']);

    return $zone->match($address);
  }

}
