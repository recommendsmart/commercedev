<?php

namespace Drupal\project_browser\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\project_browser\EnabledSourceHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the proxy layer.
 */
class ProjectBrowserEndpointController extends ControllerBase {

  /**
   * The EnabledSourceHandler.
   *
   * @var \Drupal\project_browser\EnabledSourceHandler
   */
  protected $enabledSource;

  /**
   * ProjectBrowser cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBin;

  /**
   * ProjectBrowserEndpointController constructor.
   *
   * @param \Drupal\project_browser\EnabledSourceHandler $enabled_source
   *   The enabled source.
   */
  public function __construct(EnabledSourceHandler $enabled_source) {
    $this->enabledSource = $enabled_source;
    $this->cacheBin = $this->cache('project_browser');

    $plugin_id = $this->enabledSource->getCurrentSource()->getPluginId();
    $cache_key = 'project_browser:enabled_source';
    $cached_enabled_source = $this->cacheBin->get($cache_key);
    if ($cached_enabled_source === FALSE || ($cached_enabled_source->data != $plugin_id)) {
      $this->cacheBin->deleteAll();
      $this->cacheBin->set($cache_key, $plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('project_browser.enabled_source'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Typically a project listing.
   */
  public function getAllProjects(Request $request) {
    $current_source = $this->enabledSource->getCurrentSource();
    if (!$current_source) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    // Validate and build query.
    $query = [
      'page' => (int) $request->query->get('page', 0),
      'limit' => (int) $request->query->get('limit', 12),
    ];

    $sort = $request->query->get('sort');
    if ($sort) {
      $query['sort'] = $sort;
    }

    $title = $request->query->get('search');
    if ($title) {
      $query['search'] = $title;
    }

    $categories = $request->query->get('categories');
    if ($categories) {
      $query['categories'] = $categories;
    }

    $maintenance_status = $request->query->get('maintenance_status');
    if ($maintenance_status) {
      $query['maintenance_status'] = $maintenance_status;
    }

    $development_status = $request->query->get('development_status');
    if ($development_status) {
      $query['development_status'] = $development_status;
    }

    $security_advisory_coverage = $request->query->get('security_advisory_coverage');
    if ($security_advisory_coverage) {
      $query['security_advisory_coverage'] = $security_advisory_coverage;
    }

    // Cache only exact query, down to the page number.
    $cache_key = 'project_browser:projects:' . md5(Json::encode($query));
    if ($projects = $this->cacheBin->get($cache_key)) {
      $projects = $projects->data;
    }
    else {
      $projects = $current_source->getProjects($query);
      $this->cacheBin->set($cache_key, $projects);
    }

    return new JsonResponse($projects);
  }

  /**
   * Returns a list of categories.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function getAllCategories(Request $request) {
    $current_source = $this->enabledSource->getCurrentSource();
    if (!$current_source) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    $cache_key = 'project_browser:categories';
    if ($categories = $this->cacheBin->get($cache_key)) {
      $categories = $categories->data;
    }
    else {
      $categories = $current_source->getCategories();
      $this->cacheBin->set($cache_key, $categories);
    }

    return new JsonResponse($categories);
  }

  /**
   * Returns a list of development status values.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function getAllDevelopmentStatus(Request $request) {
    $current_source = $this->enabledSource->getCurrentSource();
    if (!$current_source) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    $cache_key = 'project_browser:development_status';
    if ($values = $this->cacheBin->get($cache_key)) {
      $values = $values->data;
    }
    else {
      $values = $current_source->getDevelopmentStatuses();
      $this->cacheBin->set($cache_key, $values);
    }

    return new JsonResponse($values);
  }

  /**
   * Returns a list of maintenance status values.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function getAllMaintenanceStatus(Request $request) {
    $current_source = $this->enabledSource->getCurrentSource();
    if (!$current_source) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    $cache_key = 'project_browser:maintenance_status';
    if ($values = $this->cacheBin->get($cache_key)) {
      $values = $values->data;
    }
    else {
      $values = $current_source->getMaintenanceStatuses();
      $this->cacheBin->set($cache_key, $values);
    }

    return new JsonResponse($values);
  }

  /**
   * Returns a list of security coverage values.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function getAllSecurityCoverage(Request $request) {
    $current_source = $this->enabledSource->getCurrentSource();
    if (!$current_source) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    $cache_key = 'project_browser:security_coverage';
    if ($values = $this->cacheBin->get($cache_key)) {
      $values = $values->data;
    }
    else {
      $values = $current_source->getSecurityCoverages();
      $this->cacheBin->set($cache_key, $values);
    }

    return new JsonResponse($values);
  }

}
