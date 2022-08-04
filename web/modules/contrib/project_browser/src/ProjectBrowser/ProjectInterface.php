<?php

namespace Drupal\project_browser\ProjectBrowser;

/**
 * Defines an interface for API parameters.
 */
interface ProjectInterface {

  /**
   * Author of Project.
   */
  public function setAuthor($author);

  /**
   * Project created timestamp.
   */
  public function setCreatedTimestamp(int $created);

  /**
   * Project changed timestamp.
   */
  public function setChangedTimestamp(int $changed);

  /**
   * Project status.
   */
  public function setProjectStatus(int $status);

  /**
   * Project title.
   */
  public function setProjectTitle(string $title);

  /**
   * Unique identifier of Project, eg nid.
   */
  public function setId($id);

  /**
   * Project short description.
   */
  public function setSummary($body);

  /**
   * Project maintenance status code.
   */
  public function setMaintenanceStatus($maintenance_status);

  /**
   * Project machine name, formatted as if for `composer require`.
   */
  public function setMachineName(string $machine_name);

  /**
   * Images associated with the project. Logo should be the first one.
   */
  public function setImages(array $images);

  /**
   * Categories this project belongs.
   *
   * This must use one of the Drupal categories, see
   * https://www.drupal.org/project/project_module
   * to see the list.
   *
   * @param object|array $category
   *   Module category ids.
   */
  public function setModuleCategories($category);

  /**
   * Project security coverage status.
   */
  public function setSecurityAdvisoryCoverage(string $coverage);

  /**
   * URL to the project page, where someone could learn more about this project.
   */
  public function setProjectUrl(string $machine_name);

  /**
   * Release-wise project usage count.
   *
   * @param object|array $usage
   *   Release-wise project usage count.
   */
  public function setProjectUsage($usage);

  /**
   * Total usage count of all releases.
   */
  public function setProjectUsageTotal(int $usage_total);

  /**
   * Project star user count.
   */
  public function setProjectStarUserCount(int $star_user_count);

  /**
   * Set if the project is considered active or not.
   *
   * @param bool $is_active
   *   Value to set.
   */
  public function setIsActive(bool $is_active);

  /**
   * Set if the project is considered covered or not.
   *
   * @param bool $is_covered
   *   Value to set.
   */
  public function setIsCovered(bool $is_covered);

  /**
   * Set if the project is considered maintained or not.
   *
   * @param bool $is_maintained
   *   Value to set.
   */
  public function setIsMaintained(bool $is_maintained);

  /**
   * Set whether the project is compatible with the current Drupal installation.
   *
   * @param bool $compatible
   *   Whether the project is compatible or not.
   */
  public function setIsCompatible(bool $compatible);

  /**
   * Warnings related to installing a given module.
   *
   * @param string[] $warnings
   *   Warnings about the module to present the the user.
   */
  public function setWarnings(array $warnings);

}
