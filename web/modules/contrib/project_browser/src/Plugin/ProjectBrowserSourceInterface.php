<?php

namespace Drupal\project_browser\Plugin;

use Drupal\project_browser\ProjectBrowser\ProjectsResultsPage;

/**
 * Defines an interface for a Project Browser source.
 *
 * @see \Drupal\project_browser\Annotation\ProjectBrowserSource
 * @see \Drupal\project_browser\Plugin\ProjectBrowserSourceManager
 * @see plugin_api
 */
interface ProjectBrowserSourceInterface {

  /**
   * Gets all the projects available from this source.
   *
   * @param array $query
   *   The query string params from the frontend request.
   *
   *   The expected parameters will be:
   *   - page: Page number.
   *   - limit: Number of elements per page.
   *   - sort: Field to do the sorting on.
   *   - direction: 'ASC' or 'DESC'.
   *   - search: Search term.
   *   - categories: Comma separated list of IDs.
   *   - maintenance_status: Comma separated list of IDs.
   *   - development_status: Comma separated list of IDs.
   *   - security_advisory_coverage: Comma separated list of IDs.
   *
   * @return \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage
   *   Returns a \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage.
   */
  public function getProjects(array $query = []): ProjectsResultsPage;

  /**
   * Gets a list of all available categories.
   *
   * @return array
   *   List of categories.
   */
  public function getCategories(): array;

  /**
   * Returns the actual available maintenance options that plugins offer.
   *
   * @return array
   *   List of security coverages values.
   */
  public function getSecurityCoverages(): array;

  /**
   * Returns the available security options that plugins will parse.
   *
   * @return array
   */
  public function getSecurityOptions(): array;

  /**
   * Returns the actual available maintenance options that plugins offer.
   *
   * @return array
   *   List of maintenance status values.
   */
  public function getMaintenanceStatuses(): array;

  /**
   * Returns the available maintenance options that plugins will parse.
   *
   * @return array
   */
  public function getMaintenanceOptions(): array;

  /**
   * Returns the actual available security options that plugins offer.
   *
   * @return array
   *   List of development status values.
   */
  public function getDevelopmentStatuses(): array;

  /**
   * Returns the available maintenance options that plugins will parse.
   *
   * @return array
   */
  public function getDevelopmentOptions(): array;

  /**
   * Returns the available sort options that plugins will parse.
   *
   * @return array
   */
  public function getSortOptions(): array;

  /**
   * Determines whether the project is considered to be covered.
   *
   * Based on the security advisory values.
   *
   * @param array $project
   *   Project to check.
   *
   * @return bool
   *   Whether is considered secure or not.
   */
  public function projectIsCovered(array $project): bool;

  /**
   * Determines whether the project is considered to be active.
   *
   * Based on the maintenance status values.
   *
   * @param array $project
   *   Project to check.
   *
   * @return bool
   *   Whether is considered active or not.
   */
  public function projectIsActive(array $project): bool;

  /**
   * Determines whether the project is considered to be maintained.
   *
   * Based on the development status values.
   *
   * @param array $project
   *   Project to check.
   *
   * @return bool
   *   Whether is considered maintained or not.
   */
  public function projectIsMaintained(array $project): bool;

}
