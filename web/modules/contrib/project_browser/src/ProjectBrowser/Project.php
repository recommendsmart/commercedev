<?php

namespace Drupal\project_browser\ProjectBrowser;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a single Project.
 *
 * Properties set by
 * \Drupal\project_browser\ProjectBrowser\ProjectBrowserSourceInterface
 * are used by front-end.
 *
 * These properties will be exposed to the frontend.
 */
class Project implements ProjectInterface {

  /**
   * Constructor.
   *
   * @var object|array $project
   *   An object/array with at least each of these properties/values
   *   set as a key on it.
   */
  public function __construct($project) {
    if (is_object($project)) {
      $project = (array) $project;
    }

    if (is_array($project)) {
      $this->setAuthor($project['author']);
      $this->setCreatedTimestamp($project['created']);
      $this->setChangedTimestamp($project['changed']);
      $this->setProjectStatus($project['status']);
      $this->setProjectTitle($project['title']);
      $this->setId($project['nid']);
      $this->setSummary($project['body']);
      $this->setImages($project['field_project_images']);
      $this->setMaintenanceStatus($project['field_maintenance_status']);
      $this->setMachineName($project['field_project_machine_name']);
      $this->setModuleCategories($project['field_module_categories']);
      $this->setSecurityAdvisoryCoverage($project['field_security_advisory_coverage']);
      $this->setProjectUrl($project['field_project_machine_name']);
      $this->setProjectUsage($project['project_usage']);
      $this->setProjectUsageTotal($project['project_usage_total']);
      $this->setProjectStarUserCount($project['flag_project_star_user_count']);
      $this->setIsActive($project['is_active']);
      $this->setIsCovered($project['is_covered']);
      $this->setIsMaintained($project['is_maintained']);
      $this->setIsCompatible($project['is_compatible']);
      if (isset($project['warnings'])) {
        $this->setWarnings($project['warnings']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthor($author) {
    $this->author = $author;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTimestamp(int $created) {
    $this->created = $created;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTimestamp(int $changed) {
    $this->changed = $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectStatus(int $status) {
    $this->status = $status;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectTitle(string $title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function setSummary($body) {
    $this->body = $body;
    if (is_object($body)) {
      if (!property_exists($this->body, 'value')) {
        $this->body->value = '';
      }
      if (!property_exists($this->body, 'summary')) {
        $this->body->summary = '';
      }
      if (!$this->body->summary) {
        $this->body->summary = $this->body->value;
      }
      $this->body->summary = Html::escape(strip_tags($this->body->summary));
      $this->body->summary = Unicode::truncate($this->body->summary, 200, TRUE, TRUE);
    }
    elseif (is_array($body)) {
      if (empty($this->body['summary'])) {
        $this->body['summary'] = $this->body['value'] ?? '';
      }
      $this->body['summary'] = Html::escape(strip_tags($this->body['summary']));
      $this->body['summary'] = Unicode::truncate($this->body['summary'], 200, TRUE, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setImages(array $images) {
    $this->field_project_images = $images;
  }

  /**
   * {@inheritdoc}
   */
  public function setMaintenanceStatus($maintenance_status) {
    $this->field_maintenance_status = $maintenance_status;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName(string $machine_name) {
    $this->field_project_machine_name = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleCategories($category) {
    $this->field_module_categories = $category;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecurityAdvisoryCoverage(string $coverage) {
    $this->field_security_advisory_coverage = $coverage;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectUrl(string $machine_name) {
    $this->url = 'https://www.drupal.org/project/' . $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectUsage($usage) {
    $this->project_usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectUsageTotal(int $usage_total) {
    $this->project_usage_total = $usage_total;
  }

  /**
   * {@inheritdoc}
   */
  public function setProjectStarUserCount(int $star_user_count) {
    $this->flag_project_star_user_count = $star_user_count;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsActive(bool $is_active) {
    $this->is_active = $is_active;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsCovered(bool $is_covered) {
    $this->is_covered = $is_covered;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsMaintained(bool $is_maintained) {
    $this->is_maintained = $is_maintained;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsCompatible(bool $compatible) {
    $this->is_compatible = $compatible;
  }

  /**
   * {@inheritdoc}
   */
  public function setWarnings(array $warnings) {
    $this->warnings = $warnings;
  }

}
