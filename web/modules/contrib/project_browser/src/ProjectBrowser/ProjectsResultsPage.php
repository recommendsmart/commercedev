<?php

namespace Drupal\project_browser\ProjectBrowser;

/**
 * One page of search results from a query.
 */
class ProjectsResultsPage {
  /**
   * Total results that match the query.
   *
   * @var int
   */
  public $totalResults;

  /**
   * List of projects for one page of the query.
   *
   * @var array
   *   An array of \Drupal\project_browser\ProjectBrowser\Project
   */
  public $list = [];

  /**
   * Constructor.
   *
   * @param int $total_results
   *   The total results that match the query.
   * @param array $list
   *   The list of projects for one page.
   */
  public function __construct(int $total_results, array $list) {
    $this->totalResults = $total_results;
    $this->list = $list;
  }

}
