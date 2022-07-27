<?php

namespace Drupal\commerce_avatax\Plugin\Commerce\TaxType;

use Drupal\commerce_avatax\AvataxLibInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\RemoteTaxTypeBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the AvaTax remote tax type.
 *
 * @CommerceTaxType(
 *   id = "avatax",
 *   label = "AvaTax",
 * )
 */
class Avatax extends RemoteTaxTypeBase {

  /**
   * The AvaTax library.
   *
   * @var \Drupal\commerce_avatax\AvataxLibInterface
   */
  protected $avataxLib;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AvaTax object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_avatax\AvataxLibInterface $avatax_lib
   *   The AvaTax library.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, AvataxLibInterface $avatax_lib, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $event_dispatcher);

    $this->avataxLib = $avatax_lib;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_avatax.avatax_lib'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_inclusive' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    $config = $this->configFactory->get('commerce_avatax.settings');
    return !$config->get('disable_tax_calculation');
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $response_body = $this->avataxLib->transactionsCreate($order);

    // Do not go further unless there have been lines added.
    if (empty($response_body['lines'])) {
      return;
    }
    $currency_code = $order->getTotalPrice() ? $order->getTotalPrice()->getCurrencyCode() : $order->getStore()->getDefaultCurrencyCode();
    $adjustments = [];
    $applied_adjustments = [];
    foreach ($response_body['lines'] as $tax_adjustment) {
      $label = isset($tax_adjustment['details'][0]['taxName']) ? Html::escape($tax_adjustment['details'][0]['taxName']) : $this->t('Sales tax');
      $adjustments[$tax_adjustment['lineNumber']] = [
        'amount' => $tax_adjustment['tax'],
        'label' => $label,
      ];
    }

    // Add tax adjustments to order items.
    foreach ($order->getItems() as $order_item) {
      if (!isset($adjustments[$order_item->uuid()])) {
        continue;
      }
      $order_item->addAdjustment(new Adjustment([
        'type' => 'tax',
        'label' => $adjustments[$order_item->uuid()]['label'],
        'amount' => new Price((string) $adjustments[$order_item->uuid()]['amount'], $currency_code),
        'source_id' => $this->pluginId . '|' . $this->parentEntity->id(),
      ]));
      $applied_adjustments[$order_item->uuid()] = $order_item->uuid();
    }

    // If we still have Tax adjustments to apply, add a single one to the order.
    $remaining_adjustments = array_diff_key($adjustments, $applied_adjustments);
    if (!$remaining_adjustments) {
      return;
    }
    $tax_adjustment_total = NULL;
    // Calculate the total Tax adjustment to add.
    foreach ($remaining_adjustments as $remaining_adjustment) {
      $adjustment_amount = new Price((string) $remaining_adjustment['amount'], $currency_code);
      $tax_adjustment_total = $tax_adjustment_total ? $tax_adjustment_total->add($adjustment_amount) : $adjustment_amount;
    }
    $order->addAdjustment(new Adjustment([
      'type' => 'tax',
      'label' => $this->t('Sales tax'),
      'amount' => $tax_adjustment_total,
      'source_id' => $this->pluginId . '|' . $this->parentEntity->id(),
    ]));
  }

}
