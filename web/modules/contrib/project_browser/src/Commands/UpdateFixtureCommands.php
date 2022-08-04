<?php

namespace Drupal\project_browser\Commands;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\Event\ProjectBrowserEvents;
use Drupal\project_browser\Event\UpdateFixtureEvent;
use Drush\Commands\DrushCommands;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class UpdateFixtureCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * The EnabledSourceHandler.
   *
   * @var \Drupal\project_browser\EnabledSourceHandler
   */
  protected $enabledSource;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new UpdateFixtureCommands object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   * @param \Drupal\project_browser\EnabledSourceHandler $enabled_source
   *   The enabled source.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EnabledSourceHandler $enabled_source, EventDispatcherInterface $event_dispatcher) {
    parent::__construct();
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->enabledSource = $enabled_source;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Update to latest modules since fixture created.
   *
   * @command update:project-modules
   * @aliases update-modules
   *
   * @usage update:project-modules
   */
  public function updateProjectModules() {
    // Log the start of the script.
    $this->loggerChannelFactory->get('project_browser')->info($this->t('Update fixture batch operations start'));
    $this->logger()->notice($this->t('Starting...'));

    // Dispatch the event so that event listeners of other source can update their fixture.
    $event = new UpdateFixtureEvent($this->enabledSource);
    $this->eventDispatcher->dispatch($event, ProjectBrowserEvents::UPDATE_FIXTURE);

    $this->logger()->notice($this->t('Completed.'));
  }

  /**
   * Generate new fixtures.
   *
   * @command update:generate-fixture
   * @aliases pb-fixture
   *
   * @usage update:generate-fixture
   */
  public function generateFixture() {
    $this->logger()->notice($this->t('Begin Fixture Generation'));
    $sandbox = [];

    while (empty($sandbox) || $sandbox['#finished'] !== TRUE) {
      $progress_message = $this->hackyFixtureMaker($sandbox);

      $this->logger()->notice($progress_message);

      if ($sandbox['#finished'] === TRUE || $progress_message === 'Fixture generation complete') {
        $this->populateFromFixture();
        $this->logger()->notice($this->t('Writing fixture to database'));
        break;
      }
    }
    $this->logger()->notice($this->t('Updating project_browser.install'));
    $module_path = \Drupal::service('module_handler')->getModule('project_browser')->getPath();
    $install_file_contents = file_get_contents($module_path . '/project_browser.install');
    preg_match_all('/project_browser_update_(\d+)\(\)/', $install_file_contents, $matches);
    $update_hooks = $matches[1];
    sort($update_hooks);
    $new_hook_id = end($update_hooks) + 1;
    $update_hook = <<<'UPDATE'

function project_browser_update_{$new_hook_id}() {
  $connection = Database::getConnection();
  $connection->truncate('project_browser_projects')->execute();
  $connection->truncate('project_browser_categories')->execute();
  _project_browser_populate_from_fixture();
}

UPDATE;
    // Add update hook to the install file.
    file_put_contents($module_path . '/project_browser.install', $install_file_contents . str_replace('{$new_hook_id}', $new_hook_id, $update_hook));
  }

  /**
   * Batch operation for creating fixtures.
   *
   * @param array $sandbox
   *   The batch sandbox.
   *
   * @return string
   *   Progress messages.
   */
  private function hackyFixtureMaker(&$sandbox) {
    // Initialize some sandbox values on first iteration.
    if (!isset($sandbox['progress'])) {
      // The count of nodes visited so far.
      $sandbox['progress'] = 0;
      // Total nodes that must be visited.
      $sandbox['max'] = 7500;
      // A place to store messages during the run.
      $sandbox['messages'] = [];
      // Last node read via the query.
      $sandbox['current_page'] = 0;

      $sandbox['projects'] = [];
    }

    $current_source = \Drupal::service('project_browser.enabled_source')->getCurrentSource();
    if ($current_source && $current_source->getPluginId() === 'drupalorg_mockapi') {
      $query = [
        'page' => $sandbox['current_page'],
        'field_project_type' => 'full',
        'limit' => 50,
        'field_project_has_releases' => 1,
        'field_project_has_issue_queue' => 1,
        'type' => 'project_module',
        'status' => 1,
        'sort' => 'changed',
        'direction' => 'DESC',
      ];
      $eariest_possible_timestamp_reached = NULL;
      $drupal_org_response = $current_source->getProjectsFromSource($query);
      $returned_projects = $drupal_org_response['list'];

      if ($returned_projects) {
        foreach ($returned_projects as &$project) {
          $project['project_usage_total'] = 0;
          if (array_key_exists('project_usage', $project)) {
            foreach ($project['project_usage'] as $usage) {
              $project['project_usage_total'] += $usage;
            }
          }

          if (empty($project['body']['value'])) {
            $project['body']['value'] = '';
          }
          if (empty($project['body']['summary'])) {
            $project['body']['summary'] = $project['body']['value'];
          }
          $project['body']['summary'] = Xss::filter(strip_tags($project['body']['summary']));
          $project['body']['summary'] = Unicode::truncate($project['body']['summary'], 200, TRUE, TRUE);

          // Once we hit projects that haven't been updated since March 13, 2020, we
          // know they aren't compatible because it is before
          // https://www.drupal.org/node/3119415.
          if ($project['changed'] < 1583985600) {
            $eariest_possible_timestamp_reached = TRUE;
          }
        }
        $sandbox['current_page'] += 1;

        $projects_to_store = (array) $returned_projects;

        // Rewrite the projects array so each project has added release data and
        // unnecessary values are removed to conserve space.
        $projects_to_store = array_map(function ($a_project) use ($current_source) {
          $the_project = (array) $a_project;
          $releases = $current_source->getProjectReleasesFromSource($the_project['field_project_machine_name']);
          if (!empty($releases['releases'])) {
            $compatible_releases = array_filter($releases['releases'], function ($release) {
              if (!empty($release['core_compatibility'])) {
                try {
                  // Apparently there are multiple projects that have invalid
                  // version strings.
                  return Semver::satisfies(\Drupal::VERSION, $release['core_compatibility']);
                }
                catch (\Exception $exception) {
                  // Don't include releases with invalid compatibility strings.
                  return FALSE;
                }
              }
              return FALSE;
            });
            if (empty($compatible_releases)) {
              // Don't include projects without any compatible releases.
              return NULL;
            }
          }
          else {
            // Don't include projects without releases.
            return NULL;
          }

          // To keep filesize down, remove unnecessary items from release data.
          $the_project['releases'] = array_map(function ($release) {
            return [
              'version' => $release['version'],
              'status' => $release['status'],
              'date' => $release['date'] ?? NULL,
              'core_compatibility' => $release['core_compatibility'],
            ];
          }, $compatible_releases);
          $current_source->truncateProjectData($the_project);
          return $the_project;
        }, $projects_to_store);

        $projects_to_store = array_filter($projects_to_store);
        $sandbox['projects'] = array_merge($sandbox['projects'], $projects_to_store);
        $sandbox['progress'] += count($sandbox['projects']);
        $sandbox['#finished'] = count($sandbox['projects']) >= $sandbox['max'] || $eariest_possible_timestamp_reached ? TRUE : (count($sandbox['projects']) / $sandbox['max']);
      }
      else {
        $sandbox['#finished'] = TRUE;
      }
    }
    else {
      $sandbox['#finished'] = TRUE;
    }

    if ($sandbox['#finished'] === TRUE) {
      $module_path = \Drupal::service('module_handler')->getModule('project_browser')->getPath();
      file_put_contents($module_path . '/fixtures/project_data.json', '[]');
      file_put_contents($module_path . '/fixtures/categories.json', '[]');

      $projects = $sandbox['projects'];

      $category_values = [];

      // Map fixture values to DB columns.
      $values = array_map(function ($project) use (&$category_values) {
        if (!empty($project['taxonomy_vocabulary_3'])) {
          foreach ($project['taxonomy_vocabulary_3'] as $category) {
            $category_values[] = [
              'tid' => $category['id'],
              'pid' => $project['nid'],
            ];
          }
        }

        return [
          'nid' => $project['nid'],
          'title' => $project['title'],
          'author' => (string) @$project['author']['name'],
          'created' => $project['created'],
          'changed' => $project['changed'],
          'project_usage_total' => $project['project_usage_total'] ?? 0,
          'taxonomy_vocabulary_44' => $project['taxonomy_vocabulary_44']['id'],
          'taxonomy_vocabulary_46' => $project['taxonomy_vocabulary_46']['id'],
          'status' => $project['status'],
          'field_security_advisory_coverage' => $project['field_security_advisory_coverage'],
          'flag_project_star_user_count' => $project['flag_project_star_user_count'] ?? 0,
          'field_project_type' => $project['field_project_type'] ?? '',
          'project_data' => serialize($project),
        ];
      }, $projects);

      $used_nids = [];
      $this->logger()->notice('creating project_data.json file');
      $temp_array = Json::decode(file_get_contents($module_path . '/fixtures/project_data.json'));
      foreach ($values as $record) {
        if (in_array($record['nid'], $used_nids)) {
          continue;
        }
        $used_nids[] = $record['nid'];
        array_push($temp_array, (object) $record);
      }

      $all_records = Json::encode($temp_array);
      file_put_contents($module_path . '/fixtures/project_data.json', $all_records);

      $used_primary = [];
      $this->logger()->notice('creating categories.json file');
      $temp_array = Json::decode(file_get_contents($module_path . '/fixtures/categories.json'));

      foreach ($category_values as $record) {
        if (in_array($record['tid'] . $record['pid'], $used_primary)) {
          continue;
        }
        $used_primary[] = $record['tid'] . $record['pid'];
        array_push($temp_array, (object) $record);
        $all_categories = Json::encode($temp_array);
      }
      file_put_contents($module_path . '/fixtures/categories.json', $all_categories);

      return 'Fixture generation complete';
    }
    else {
      $last = $sandbox['projects'][array_key_last($sandbox['projects'])];
      return 'Page: ' . $sandbox['current_page'] . ' | Projects added:' . count($sandbox['projects']) . ' | ' . $last['changed'] . ' | ' . $sandbox['#finished'] * 100 . '%';
    }
  }

  /**
   * Updates database from fixtures.
   */
  private function populateFromFixture() {
    $connection = Database::getConnection();
    $connection->truncate('project_browser_projects')->execute();
    $connection->truncate('project_browser_categories')->execute();
    $module_path = \Drupal::service('module_handler')->getModule('project_browser')->getPath();
    $most_recent_change = 0;

    $projects = Json::decode(file_get_contents($module_path . '/fixtures/project_data.json'));
    $projects_chunk = array_chunk($projects, 1000);
    foreach ($projects_chunk as $chunk_projects) {
      // Insert fixture data to the database.
      $query = $connection->insert('project_browser_projects')->fields([
        'nid',
        'title',
        'author',
        'created',
        'changed',
        'project_usage_total',
        'maintenance_status',
        'development_status',
        'status',
        'field_security_advisory_coverage',
        'flag_project_star_user_count',
        'field_project_type',
        'project_data',
      ]);
      foreach ($chunk_projects as $project) {
        if ($project['changed'] > $most_recent_change) {
          $most_recent_change = $project['changed'];
        }
        // Map from fixture format to the expected by the database.
        $project_data = unserialize($project['project_data']);
        $project['maintenance_status'] = $project_data['taxonomy_vocabulary_44']['id'];
        $project['development_status'] = $project_data['taxonomy_vocabulary_46']['id'];

        $query->values($project);
      }
      $query->execute();
    }

    $categories = Json::decode(file_get_contents($module_path . '/fixtures/categories.json'));
    $category_query = $connection->insert('project_browser_categories')->fields([
      'tid',
      'pid',
    ]);
    foreach ($categories as $category) {
      $category_query->values((array) $category);
    }
    $category_query->execute();

    \Drupal::state()->set('project_browser.last_imported', $most_recent_change);
  }

}
