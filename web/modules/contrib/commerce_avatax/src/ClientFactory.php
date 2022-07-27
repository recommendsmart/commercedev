<?php

namespace Drupal\commerce_avatax;

use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Http\ClientFactory as CoreClientFactory;

/**
 * API Client factory.
 */
class ClientFactory {

  /**
   * The client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The extensions list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $extensionList;

  /**
   * Constructs a new AvaTax ClientFactory object.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The client factory.
   * @param \Drupal\Core\Extension\ExtensionList $extension_list
   *   The extension list.
   */
  public function __construct(CoreClientFactory $client_factory, ExtensionList $extension_list) {
    $this->clientFactory = $client_factory;
    $this->extensionList = $extension_list;
  }

  /**
   * Gets an API client instance.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The API client.
   */
  public function createInstance(array $config) {
    switch ($config['api_mode']) {
      case 'production':
        $base_uri = 'https://rest.avatax.com/';
        break;

      case 'development':
      default:
        $base_uri = 'https://sandbox-rest.avatax.com/';
        break;
    }

    // Specify the x-Avalara-Client header.
    $server_machine_name = gethostname();
    $module_info = $this->extensionList->getExtensionInfo('commerce_avatax');
    $version = !empty($module_info['version']) ? $module_info['version'] : '8.x-1.x';
    $x_avalara_client = "Drupal Commerce; Version [$version]; REST; V2; [$server_machine_name]";

    $options = [
      'base_uri' => $base_uri,
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($config['account_id'] . ':' . $config['license_key']),
        'Content-Type' => 'application/json',
        'x-Avalara-UID' => 'a0o33000003waOC',
        'x-Avalara-Client' => $x_avalara_client,
      ],
    ];

    return $this->clientFactory->fromOptions($options);
  }

}
