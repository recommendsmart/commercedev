<?php

namespace Drupal\project_browser_devel\Plugin\ProjectBrowserSource;

use Drupal\Component\Utility\Random;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Random data plugin. Used mostly for testing.
 *
 * To enable this source:
 * - `drush config:set project_browser.admin_settings enabled_source random_data`
 *
 * @ProjectBrowserSource(
 *   id = "random_data",
 *   label = @Translation("Random data"),
 *   description = @Translation("Gets random project and filters information"),
 * )
 */
class RandomDataPlugin extends ProjectBrowserSourceBase implements ContainerFactoryPluginInterface {

  /**
   * Utility to create random data.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * Constructs a MockDrupalDotOrg object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->randomGenerator = new Random();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Generate random IDs and labels.
   *
   * @param int $array_length
   *   Length of the array to generate.
   *
   * @return array
   *   Array of random IDs and names.
   */
  protected function getRandomIdsAndNames($array_length = 4): array {
    $data = [];
    for ($i = 0; $i < $array_length; $i++) {
      $data[] = [
        'id' => uniqid(),
        'name' => $this->randomGenerator->word(rand(6, 10)),
      ];
    }

    return $data;
  }

  /**
   * Returns a random date.
   *
   * @return int
   *   Random timestamp.
   */
  protected function getRandomDate() {
    return rand(strtotime('2 years ago'), strtotime('today'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDevelopmentStatuses(): array {
    return $this->getRandomIdsAndNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaintenanceStatuses(): array {
    return $this->getRandomIdsAndNames(6);
  }

  /**
   * {@inheritdoc}
   */
  public function getSecurityCoverages(): array {
    return $this->getRandomIdsAndNames(2);
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    return $this->getRandomIdsAndNames(20);
  }

  /**
   * {@inheritdoc}
   */
  protected function convertQueryOptions(array $query = []): array {
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $query = []) : ProjectsResultsPage {
    $projects = [];
    $number_of_projects = rand(12, 36);
    $categories = $this->getCategories();
    $security_values = $this->getSecurityCoverages();
    $maintenance_values = $this->getMaintenanceStatuses();
    $broken_image = 'https://image.not/found' . uniqid() . '.jpg';
    $good_image = 'https://picsum.photos/600/400';
    for ($i = 0; $i < $number_of_projects; $i++) {
      $machine_name = strtolower($this->randomGenerator->word(10));
      $category = array_rand($categories);
      $security = array_rand($security_values);
      $maintenance = array_rand($maintenance_values);
      $project = [
        'author' => [
          'name' => $this->randomGenerator->word(10),
        ],
        'created' => $this->getRandomDate(),
        'changed' => $this->getRandomDate(),
        'status' => rand(0, 1),
        'title' => ucwords($machine_name),
        'nid' => uniqid(),
        'body' => [
          'summary' => $this->randomGenerator->paragraphs(1),
        ],
        'field_project_images' => [
          [
            'file' => [
              'uri' => ($i % 3) ? $good_image : $broken_image,
              'resource' => 'image',
            ],
            'alt' => $machine_name . ' logo',
          ],
        ],
        'field_maintenance_status' => $maintenance_values[$maintenance],
        'field_module_categories' => [$categories[$category]],
        'field_security_advisory_coverage' => $security_values[$security]['id'],
        'field_project_machine_name' => $machine_name,
        'is_compatible' => (bool) ($i % 4),
        'project_usage' => [],
        'project_usage_total' => rand(0, 100000),
        'flag_project_star_user_count' => rand(0, 100),
      ];
      $project['is_covered'] = $this->projectIsCovered($project);
      $project['is_active'] = $this->projectIsActive($project);
      $project['is_maintained'] = $this->projectIsMaintained($project);
      $projects[] = new Project($project);
    }

    return new ProjectsResultsPage(count($projects), $projects);
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsCovered(array $project): bool {
    return (bool) rand(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsActive(array $project): bool {
    return (bool) rand(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function projectIsMaintained(array $project): bool {
    return (bool) rand(0, 1);
  }

}
