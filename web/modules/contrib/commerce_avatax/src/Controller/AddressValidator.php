<?php

namespace Drupal\commerce_avatax\Controller;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\commerce_avatax\AvataxLibInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Address validator controller.
 */
class AddressValidator extends ControllerBase {

  /**
   * The AvaTax library.
   *
   * @var \Drupal\commerce_avatax\AvataxLibInterface
   */
  protected $avataxLib;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new AddressValidator object.
   *
   * @param \Drupal\commerce_avatax\AvataxLibInterface $avatax_lib
   *   The AvaTax library.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(AvataxLibInterface $avatax_lib, Renderer $renderer, CountryRepositoryInterface $country_repository) {
    $this->avataxLib = $avatax_lib;
    $this->renderer = $renderer;
    $this->countryRepository = $country_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_avatax.avatax_lib'),
      $container->get('renderer'),
      $container->get('address.country_repository')
    );
  }

  /**
   * Provides address validation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function process(Request $request) {
    $content = $request->getContent();
    if (empty($content)) {
      return new JsonResponse([
        'valid' => FALSE,
        'output' => NULL,
      ], 400);
    }

    $data = $this->avataxLib->validateAddress(Json::decode($content));
    // If we have suggestion show it.
    if (!empty($data['suggestion'])) {
      $data['output'] = $this->formatSuggestedAddress($data['original'], $data['suggestion'], $data['fields']);
      $data['payload'] = base64_encode(Json::encode($data['suggestion']));
    }

    // When we have original address, and we know that is not valid.
    elseif (!empty($data['original']) && !$data['valid']) {
      $data['output'] = $this->formatSuggestedAddress($data['original']);
    }

    return new JsonResponse($data);
  }

  /**
   * Format an address for use in modal.
   *
   * @param array $original
   *   Original formatted address.
   * @param array $suggestion
   *   Suggested formatted address.
   * @param array $fields
   *   Fields which are different on suggestion and original address.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Return a formatted address for use in the order request.
   */
  protected function formatSuggestedAddress(array $original, array $suggestion = [], array $fields = []) {
    $countries = $this->countryRepository->getAll();

    if (isset($original['country_code'])) {
      $original['country_code'] = $countries[$original['country_code']] ?? $original['country_code'];
    }

    if (isset($suggestion['country_code'])) {
      $suggestion['country_code'] = $countries[$suggestion['country_code']] ?? $suggestion['country_code'];
    }

    $build = [
      '#theme' => 'avatax_address',
      '#original' => $original,
      '#suggestion' => $suggestion,
      '#fields' => $fields,
    ];

    // Render output for modal.
    return $this->renderer->renderPlain($build);
  }

}
