<?php

namespace Drupal\project_browser\Plugin\ProjectBrowserSource;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source that mocks the Drupal.org API that's still to-be-built.
 *
 * To enable this source (this is the default for the module):
 * - `drush config:set project_browser.admin_settings enabled_source drupalorg_mockapi`
 *
 * @ProjectBrowserSource(
 *   id = "drupalorg_mockapi",
 *   label = @Translation("Drupal.org (mocked)"),
 *   description = @Translation("Gets project and filters information from a mock API"),
 * )
 */
class MockDrupalDotOrg extends ProjectBrowserSourceBase implements ContainerFactoryPluginInterface {

  /**
   * This is what the Mock understands as "Covered" modules.
   *
   * @var array
   */
  const COVERED_VALUES = ['covered'];

  /**
   * This is what the Mock understands as "Active" modules.
   *
   * @var array
   */
  const ACTIVE_VALUES = [9988, 13030];

  /**
   * This is what the Mock understands as "Maintained" modules.
   *
   * @var array
   */
  const MAINTAINED_VALUES = [13028, 19370, 9990];

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The state object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ProjectBrowser cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBin;

  /**
   * Constructs a MockDrupalDotOrg object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_bin
   *   The cache bin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, Connection $database, ClientInterface $http_client, StateInterface $state, CacheBackendInterface $cache_bin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->database = $database;
    $this->httpClient = $http_client;
    $this->state = $state;
    $this->cacheBin = $cache_bin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('project_browser'),
      $container->get('database'),
      $container->get('http_client'),
      $container->get('state'),
      $container->get('cache.project_browser'),
    );
  }

  /**
   * Gets status vocabulary info from the Drupal.org json endpoint.
   *
   * @param int $taxonomy_id
   *   The id of the taxonomy being retrieved.
   *
   * @return array|array[]
   *   An array with the term id, name and description.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown if request is unsuccessful.
   */
  protected function getStatuses(int $taxonomy_id) {
    $cached_statuses = $this->cacheBin->get("MockDrupalDotOrg:taxonomy_$taxonomy_id");
    if ($cached_statuses) {
      return $cached_statuses->data;
    }
    $url = "https://www.drupal.org/api-d7/taxonomy_term.json?vocabulary=$taxonomy_id";
    $response = \Drupal::httpClient()->request('GET', $url);
    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException("Request to $url failed, returned {$response->getStatusCode()} with reason: {$response->getReasonPhrase()}");
    }
    $body = Json::decode($response->getBody()->getContents());
    $list = $body['list'];
    $list = array_map(function ($item) {
      $item['id'] = $item['tid'];
      return array_intersect_key($item, array_flip(['id', 'name', 'description']));
    }, $list);
    $this->cacheBin->set("MockDrupalDotOrg:taxonomy_$taxonomy_id", $list);

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getDevelopmentStatuses(): array {
    return $this->getStatuses(46);
  }

  /**
   * {@inheritdoc}
   */
  public function getMaintenanceStatuses(): array {
    return $this->getStatuses(44);
  }

  /**
   * {@inheritdoc}
   */
  public function getSecurityCoverages(): array {
    return [
      ['id' => 'covered', 'name' => 'Covered'],
      ['id' => 'not-covered', 'name' => 'Not covered'],
    ];
  }

  /**
   * Convert the sort entry within the query from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertSort(array &$query) {
    if (!empty($query['sort'])) {
      $options_available = $this->getSortOptions();
      if (!in_array($query['sort'], array_keys($options_available))) {
        unset($query['sort']);
      }
      else {
        // Valid value.
        switch ($query['sort']) {
          case 'usage_total':
          case 'best_match':
            $query['sort'] = 'project_usage_total';
            $query['direction'] = 'DESC';
            break;

          case 'a_z':
            $query['sort'] = 'title';
            $query['direction'] = 'ASC';
            break;

          case 'z_a':
            $query['sort'] = 'title';
            $query['direction'] = 'DESC';
            break;

          case 'created':
            $query['sort'] = 'created';
            $query['direction'] = 'DESC';
            break;

        }
      }
    }
  }

  /**
   * Convert the maintenance entry within the query from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertMaintenance(array &$query) {
    if (!empty($query['maintenance_status'])) {
      $options_available = $this->getMaintenanceOptions();
      if (!in_array($query['maintenance_status'], array_keys($options_available))) {
        unset($query['maintenance_status']);
      }
      else {
        // Valid value.
        switch ($query['maintenance_status']) {
          case self::MAINTAINED_ID:
            $query['maintenance_status'] = self::MAINTAINED_VALUES;
            break;

          case 'all':
            unset($query['maintenance_status']);
            break;

        }
      }
    }
  }

  /**
   * Convert the development entry within the query from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertDevelopment(array &$query) {
    if (!empty($query['development_status'])) {
      $options_available = $this->getDevelopmentOptions();
      if (!in_array($query['development_status'], array_keys($options_available))) {
        unset($query['development_status']);
      }
      else {
        // Valid value.
        switch ($query['development_status']) {
          case self::ACTIVE_ID:
            $query['development_status'] = self::ACTIVE_VALUES;
            break;

          case 'all':
            unset($query['development_status']);
            break;

        }
      }
    }
  }

  /**
   * Convert the security entry within the query from received to expected by DB.
   *
   * @param array $query
   *   Query array to transform.
   */
  protected function convertSecurity(array &$query) {
    if (!empty($query['security_advisory_coverage'])) {
      $options_available = $this->getSecurityOptions();
      if (!in_array($query['security_advisory_coverage'], array_keys($options_available))) {
        unset($query['security_advisory_coverage']);
      }
      else {
        // Valid value.
        switch ($query['security_advisory_coverage']) {
          case self::COVERED_ID:
            $query['security_advisory_coverage'] = self::COVERED_VALUES;
            break;

          case 'all':
            $keys = [];
            $options = $this->getSecurityCoverages();
            foreach ($options as $option) {
              $keys[] = $option['id'];
            }
            $query['security_advisory_coverage'] = $keys;
            break;

        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function convertQueryOptions(array $query = []): array {
    $this->convertSort($query);
    $this->convertMaintenance($query);
    $this->convertDevelopment($query);
    $this->convertSecurity($query);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    $module_path = \Drupal::service('module_handler')->getModule('project_browser')->getPath();
    $categories = Json::decode(file_get_contents($module_path . '/fixtures/category_list.json')) ?? [];
    // Change 'tid' into 'id'.
    foreach ($categories as &$category) {
      $category['id'] = $category['tid'];
      unset($category['tid']);
    }

    return $categories;
  }

  /**
   * Loops through a typical taxonomy array and makes the key be the ID.
   *
   * @param array $array
   *   Array to loop.
   *
   * @return array
   *   Keyed array by ID.
   */
  protected function getKeyedArray(array $array): array {
    $keyed_array = [];
    foreach ($array as $item) {
      $keyed_array[$item['id']] = $item['name'];
    }

    return $keyed_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []) : ProjectsResultsPage {
    $api_response = $this->fetchProjects($query);
    $categories = $this->getCategories();
    $development_status_values = $this->getDevelopmentStatuses();
    $maintenance_status_values = $this->getMaintenanceStatuses();

    // Map different set of values by key to provide the names into the attributes too.
    $keyed_categories = $this->getKeyedArray($categories);
    $keyed_development_status_values = $this->getKeyedArray($development_status_values);
    $keyed_maintenance_status_values = $this->getKeyedArray($maintenance_status_values);

    $returned_list = [];
    if ($api_response) {
      foreach ($api_response['list'] as $project) {
        if (is_object($project)) {
          $project = (array) $project;
        }
        // Map any properties from the mock to the expected in Project.
        $project['field_module_categories'] = $project['taxonomy_vocabulary_3'];
        if (!empty($project['field_module_categories'])) {
          // Add name property to each category so it can be rendered.
          foreach ($project['field_module_categories'] as &$field_module_category) {
            $field_module_category['name'] = $keyed_categories[$field_module_category['id']] ?? '';
          }
        }

        if (!empty($project['taxonomy_vocabulary_44'])) {
          $project['field_maintenance_status'] = [
            'id' => $project['taxonomy_vocabulary_44']['id'],
            'name' => $keyed_maintenance_status_values[$project['taxonomy_vocabulary_44']['id']],
          ];
        }

        if (!empty($project['taxonomy_vocabulary_46'])) {
          $project['field_development_status'] = [
            'id' => $project['taxonomy_vocabulary_46']['id'],
            'name' => $keyed_development_status_values[$project['taxonomy_vocabulary_46']['id']],
          ];
        }

        // Mock projects are filtered and made sure that they are compatible
        // before we even put them in the database.
        $project['is_compatible'] = TRUE;

        // Compute total project usage.
        if (!empty($project['project_usage'])) {
          $project_usage_total = 0;
          foreach ($project['project_usage'] as $usage) {
            $project_usage_total += $usage;
          }
          $project['project_usage_total'] = $project_usage_total;
        }
        else {
          $project['project_usage'] = [];
          $project['project_usage_total'] = 0;
        }

        $project['flag_project_star_user_count'] = 0;
        $project['is_covered'] = $this->projectIsCovered($project);
        $project['is_active'] = $this->projectIsActive($project);
        $project['is_maintained'] = $this->projectIsMaintained($project);
        $project['warnings'] = $this->getWarnings($project);

        $returned_list[] = new Project($project);
      }
    }

    return new ProjectsResultsPage($api_response['total_results'] ?? 0, $returned_list);
  }

  /**
   * Fetches the projects from the mock backend.
   *
   * Here, we're querying the local database, populated from the fixture.
   * Ultimately, in the real implementation, this would be fetching over
   * the Drupal.org (JSON?) API (TBD).
   */
  protected function fetchProjects($query) {
    $query = $this->convertQueryOptions($query);
    try {
      $db_query = $this->database->select('project_browser_projects', 'pbp')
        ->fields('pbp')
        ->condition('pbp.status', 1);

      if (array_key_exists('sort', $query) && !empty($query['sort'])) {
        $sort = $query['sort'];
        $direction = (array_key_exists('direction', $query) && $query['direction'] == 'ASC') ? 'ASC' : 'DESC';
        $db_query->orderBy($sort, $direction);
      }
      else {
        // Default order.
        $db_query->orderBy('project_usage_total', 'DESC');
      }

      // Filter by maintenance status.
      if (array_key_exists('maintenance_status', $query)) {
        $db_query->condition('maintenance_status', $query['maintenance_status'], 'IN');
      }

      // Filter by development status.
      if (array_key_exists('development_status', $query)) {
        $db_query->condition('development_status', $query['development_status'], 'IN');
      }

      // Filter by security advisory coverage.
      if (array_key_exists('security_advisory_coverage', $query)) {
        $db_query->condition('field_security_advisory_coverage', $query['security_advisory_coverage'], 'IN');
      }

      // Filter by category.
      if (array_key_exists('categories', $query)) {
        $tids = explode(',', $query['categories']);
        $db_query->join('project_browser_categories', 'cat', 'pbp.nid = cat.pid');
        $db_query->condition('cat.tid', $tids, 'IN');
      }

      // Filter by search term.
      if (array_key_exists('search', $query)) {
        $title = $query['search'];
        $db_query->condition('pbp.title', "%$title%", 'LIKE');
      }
      $db_query->groupBy('pbp.nid');

      // taxonomy_vocabulary_6 = Core compatibility.
      // @todo Fire event. Allow altering query.
      $projects = new \ArrayObject();

      // If there is a specified limit, then this is a list of multiple
      // projects.
      $total_results = $db_query->countQuery()
        ->execute()
        ->fetchField();
      $offset = $query['page'] ?? 0;
      $limit = $query['limit'] ?? 50;
      $db_query->range($limit * $offset, $limit);
      $result = $db_query
        ->execute()
        ->fetchAll();
      $db_projects = array_map(function ($project_data) {
        return unserialize($project_data->project_data);
      }, $result);

      if (count($projects) > 0 || count($db_projects) > 0) {
        $projects_array = (array) $projects;
        $drupal_org_response['list'] = !empty($db_projects) ? $db_projects : $projects_array;
        $drupal_org_response['total_results'] = $total_results;
        return $drupal_org_response;
      }

      return FALSE;
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * Requests a node from the Drupal.org API.
   *
   * @param string $project
   *   The Drupal.org project name.
   *
   * @return array
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown if request is unsuccessful.
   *
   * @see https://www.drupal.org/drupalorg/docs/apis/rest-and-other-apis#s-releases
   *
   * @see https://www.drupal.org/drupalorg/docs/apis/update-status-xml
   */
  protected function requestProjectReleases(string $project): array {
    $url = "https://updates.drupal.org/release-history/$project/current";
    $response = \Drupal::httpClient()->request('GET', $url);
    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException("Request to $url failed, returned {$response->getStatusCode()} with reason: {$response->getReasonPhrase()}");
    }
    $body = $response->getBody()->getContents();
    if (strpos($body, 'No release history was found for the requested project') !== FALSE) {
      return [];
    }

    $xml = \simplexml_load_string($body);
    return Json::decode(Json::encode($xml), TRUE);
  }

  /**
   * Get a list of all Drupal.org nodes of type 'project_module'.
   *
   * @param array $query
   *   An array of query parameters. See https://www.drupal.org/i/3218285.
   *
   * @return array
   *   An array of project data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   *
   * @see https://www.drupal.org/drupalorg/docs/apis/rest-and-other-apis
   */
  public function getProjectsFromSource(array $query = []): array {
    try {
      $response = $this->httpClient->request('GET', "https://www.drupal.org/api-d7/node.json", [
        'on_stats' => static function (TransferStats $stats) use (&$url) {
          $url = $stats->getEffectiveUri();
        },
        'query' => $query,
      ]);
    }
    catch (RequestException $re) {
      // Try a second time because sometimes d.o times out the request.
      $response = $this->httpClient->request('GET', "https://www.drupal.org/api-d7/node.json", [
        'on_stats' => static function (TransferStats $stats) use (&$url) {
          $url = $stats->getEffectiveUri();
        },
        'query' => $query,
      ]);
    }

    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException("Request to $url failed, returned {$response->getStatusCode()} with reason: {$response->getReasonPhrase()}");
    }

    return Json::decode($response->getBody()->getContents());
  }

  /**
   * Strip project data of unnecessary items.
   *
   * @param array $project
   *   Data for a project.
   */
  public function truncateProjectData(array &$project) {
    if (!empty($project['field_project_images'])) {
      $project['field_project_images'] = [$project['field_project_images'][0]];
    }
    unset($project['flag_project_star_user']);
    unset($project['field_supporting_organizations']);
    unset($project['url']);
    unset($project['author']['uri']);
    unset($project['author']['id']);
    unset($project['author']['resource']);
    foreach ($project as $key => $value) {
      if (strpos($key, "\0*\0") !== FALSE) {
        if (strpos($key, 'field_project_type') !== FALSE) {
          $project['field_project_type'] = $value;
        }
        unset($project[$key]);
      }
    }
    foreach (['taxonomy_vocabulary_44', 'taxonomy_vocabulary_46'] as $value) {
      if (isset($project[$value])) {
        unset($project[$value]['uri']);
        unset($project[$value]['resource']);
      }
    }

    if (isset($project['taxonomy_vocabulary_3'])) {
      foreach ($project['taxonomy_vocabulary_3'] as $key => $value) {
        unset($project['taxonomy_vocabulary_3'][$key]['uri']);
        unset($project['taxonomy_vocabulary_3'][$key]['resource']);
      }
    }
  }

  /**
   * Update the database with any projects updated since the last update.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @throws \JsonException
   */
  public function updateMostRecentChanges() {
    $last_changed = $this->state->get('project_browser.last_imported');
    $add_to_db = [];
    $page = 0;

    // Start with a timestamp absurdly far into the future so the while loop
    // can initiate before $project_was_changed is updated with an actual
    // timestamp related to the project.
    $project_was_changed = 2583878026;
    while ($last_changed <= $project_was_changed) {
      $projects = $this->getProjectsFromSource([
        'page' => $page,
        'field_project_type' => 'full',
        'limit' => 50,
        'field_project_has_releases' => 1,
        'field_project_has_issue_queue' => 1,
        'type' => 'project_module',
        'status' => 1,
        'sort' => 'changed',
        'direction' => 'DESC',
      ]);
      foreach ($projects['list'] as $project) {
        $project_was_changed = $project['changed'];
        if ($project_was_changed < $last_changed) {
          break;
        }
        $releases = $this->getProjectReleasesFromSource($project['field_project_machine_name']);
        if (!empty($releases['releases'])) {
          $compatible_releases = array_filter($releases['releases'], function ($release) {
            if (!empty($release['core_compatibility'])) {
              try {
                // Wrap in try{} due to projects using invalid version strings.
                return Semver::satisfies(\Drupal::VERSION, $release['core_compatibility']);
              }
              catch (\Exception $exception) {
                return FALSE;
              }
            }
          });
          if (!empty($compatible_releases)) {
            $project['releases'] = array_map(function ($release) {
              return [
                'version' => $release['version'],
                'status' => $release['status'],
                'date' => $release['date'],
                'core_compatibility' => $release['core_compatibility'],
              ];
            }, $compatible_releases);
            $add_to_db[] = $project;
          }
        }
      }
      $page += 1;
    }
    foreach ($add_to_db as $project_to_update) {
      $this->truncateProjectData($project_to_update);
      $new_values = [
        'nid' => $project_to_update['nid'],
        'title' => trim($project_to_update['title']),
        'author' => $project_to_update['author']['name'],
        'created' => $project_to_update['created'],
        'changed' => $project_to_update['changed'],
        'project_usage_total' => $project_to_update['project_usage_total'] ?? 0,
        'maintenance_status' => $project_to_update['taxonomy_vocabulary_44']['id'],
        'development_status' => $project_to_update['taxonomy_vocabulary_46']['id'],
        'status' => $project_to_update['status'],
        'field_security_advisory_coverage' => $project_to_update['field_security_advisory_coverage'],
        'flag_project_star_user_count' => $project_to_update['flag_project_star_user_count'] ?? 0,
        'field_project_type' => $project_to_update['field_project_type'] ?? '',
        'project_data' => serialize($project_to_update),
      ];

      $result = $this->database->select('project_browser_projects', 'pbp')
        ->fields('pbp')
        ->condition('pbp.nid', $project_to_update['nid'])
        ->execute();
      if (!empty($result->fetchAll())) {
        $this->database->update('project_browser_projects')
          ->fields($new_values)
          ->condition('nid', $project_to_update['nid'])
          ->execute();
      }
      else {
        $this->database->insert('project_browser_projects')
          ->fields($new_values)
          ->execute();
      }

      $category_values = [];
      if (!empty($project_to_update['taxonomy_vocabulary_3'])) {
        foreach ($project_to_update['taxonomy_vocabulary_3'] as $category) {
          $result = $this->database->query("SELECT * FROM {project_browser_categories} WHERE tid = :tid AND pid = :pid", [
            ':tid' => $category['id'],
            ':pid' => $project_to_update['nid'],
          ]);
          if (empty($result->fetchAll())) {
            $category_values[] = [
              'tid' => $category['id'],
              'pid' => $project_to_update['nid'],
            ];
          }
        }
      }
      if (!empty($category_values)) {
        $category_query = $this->database->insert('project_browser_categories')
          ->fields(['tid', 'pid']);
        foreach ($category_values as $record) {
          $category_query->values($record);
        }
        $category_query->execute();
      }
    }
    $this->state->set('project_browser.last_imported', time());
  }

  /**
   * Requests a node from the Drupal.org API.
   *
   * @param string $project
   *   The Drupal.org project to get the releases from.
   *
   * @return array
   *   An array releases.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown if request is unsuccessful.
   */
  public function getProjectReleasesFromSource(string $project) {
    if ($project === 'drupal/core') {
      $project = 'drupal';
    }
    else {
      $project = str_replace(['drupal/', 'acquia/'], '', $project);
    }
    $response = $this->requestProjectReleases($project);
    if (array_key_exists('releases', $response)) {
      // Only one release.
      if (array_key_exists('name', $response['releases']['release'])) {
        $response['releases'] = [$response['releases']['release']];
      }
      // Multiple releases.
      else {
        $response['releases'] = $response['releases']['release'];
      }
    }
    // No releases.
    else {
      $response['releases'] = [];
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsCovered(array $project): bool {
    if (!empty($project['field_security_advisory_coverage'])) {
      return in_array($project['field_security_advisory_coverage'], self::COVERED_VALUES);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsActive(array $project): bool {
    if (!empty($project['field_development_status'])) {
      return in_array($project['field_development_status']['id'], self::ACTIVE_VALUES);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsMaintained(array $project): bool {
    if (!empty($project['field_maintenance_status'])) {
      return in_array($project['field_maintenance_status']['id'], self::MAINTAINED_VALUES);
    }

    return FALSE;
  }

  /**
   * Determines warning messages based on development and maintenance status.
   *
   * @param $project
   *   A project array.
   *
   * @return string[]
   *   An array of warning messages.
   */
  protected function getWarnings($project) {
    // This is based on logic from Drupal.org.
    // @see https://git.drupalcode.org/project/drupalorg/-/blob/e31465608d1380345834/drupalorg_project/drupalorg_project.module
    $warnings = [];
    $merged_vocabularies = array_merge($this->getDevelopmentStatuses(), $this->getMaintenanceStatuses());
    $statuses = array_column($merged_vocabularies, 'description', 'id');
    foreach (['taxonomy_vocabulary_44', 'taxonomy_vocabulary_46'] as $field) {
      // Maintenance status is not Actively maintained and Development status is
      // not Under active development.
      $id = $project[$field]['id'] ?? FALSE;
      if ($id && !in_array($id, [13028, 9988])) {
        // Maintenance status is Abandoned, or Development status is No further
        // development or Obsolete.
        if (in_array($id, [13032, 16538, 9994])) {
          $warnings[] = $statuses[$id];
        }
      }
    }
    return $warnings;
  }

}
