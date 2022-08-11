<?php

namespace Drupal\commerce_simple_stock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CommerceSimpleStockController.
 */
class CommerceSimpleStockController extends ControllerBase {

  /**
   * The connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request) {
    $matches = [];

    if ($input = $request->query->get('q')) {
      $query = $this->connection->select('commerce_product_variation_field_data', 'cpv');
      $query->fields('cpv', ['sku']);
      $query->fields('cpv', ['title']);
      $query->condition('cpv.sku', '%' . $input . '%', 'LIKE');
      $query->condition('cpv.langcode', 'en', 'LIKE');
      $query->range(0, 20);
      $result = $query->execute()->fetchCol();
    }

    return new JsonResponse($result);
  }

}
