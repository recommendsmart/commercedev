<?php

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Provides tests for the Project Browser UI.
 *
 * These tests rely on a module that replaces Project Browser data with
 * test data.
 *
 * @see project_browser_test_install()
 *
 * @group project_browser
 */
class ProjectBrowserUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
    'project_browser_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('project_browser.admin_settings')->set('enabled_source', 'drupalorg_mockapi')->save(TRUE);
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
    ]));
  }

  /**
   * Asserts that a given list of project titles are visible on the page.
   *
   * @param array $project_titles
   *   An array of expected titles.
   */
  protected function assertProjectsVisible(array $project_titles): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->waitForElementVisible('css', '#project-browser .project');
    foreach ($project_titles as $key => $title) {
      $this->assertEquals($title, $page->findAll('css', '#project-browser .project__title')[$key]->getText());
    }
  }

  /**
   * Asserts that a given list of pager items are present on the page.
   *
   * @param array $pager_items
   *   An array of expected pager item labels.
   */
  protected function assertPagerItems(array $pager_items): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $items = array_map(function ($element) {
      return $element->getText();
    }, $page->findAll('css', '#project-browser .pager__item'));
    $this->assertSame($pager_items, $items);
  }

  /**
   * Tests the grid view.
   */
  public function testGrid(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->getSession()->resizeWindow(1210, 1210);
    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElement('css', '#project-browser .grid');
    $grid_text = $this->getSession()->getPage()->find('css', '#project-browser .toggle-buttons .grid-button')->getText();
    $this->assertEquals('Grid', $grid_text);
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .project'));
    $assert_session->elementsCount('css', '#project-browser .project.grid', 9);
    $assert_session->pageTextNotContains('No records available');
    $page->pressButton('List');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .project.list'));
    $assert_session->elementsCount('css', '#project-browser .project.list', 9);
    $this->getSession()->resizeWindow(1100, 1100);
    $assert_session->assertNoElementAfterWait('css', '.toggle.list-button');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .project.grid'));
    $assert_session->elementsCount('css', '#project-browser .project.grid', 9);
    $this->getSession()->resizeWindow(1210, 1210);
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#project-browser .project.list'));
    $assert_session->elementsCount('css', '#project-browser .project.list', 9);
  }

  /**
   * Tests the available categories.
   */
  public function testCategories(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElement('css', '.pb-categories input[type="checkbox"]');
    $assert_session->elementsCount('css', '.pb-categories input[type="checkbox"]', 54);
  }

  /**
   * Tests category filtering.
   */
  public function testCategoryFiltering(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->waitForElement('css', '.views-exposed-form__item input[type="checkbox"]');

    // Click 'E-commerce' checkbox.
    $page->find('css', '#104')->click();

    $module_category_e_commerce_filter_selector = 'p.filters-applied:nth-child(4)';
    $module_category_e_commerce_filter_element = $page->find('css', $module_category_e_commerce_filter_selector);
    // Make sure the 'E-commerce' module category filter is applied.
    $this->assertEquals('E-commerce', $module_category_e_commerce_filter_element->find('css', '.filter-label')->getText());
    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Helvetica',
      'Astronaut Simulator',
    ]);

    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('25 Results');

    // Click 'Media' checkbox.
    $page->find('css', '#67')->click();
    // Click 'Commerce/Advertising' checkbox.
    $page->find('css', '#55')->click();

    $module_category_media_filter_selector = 'p.filters-applied:nth-child(3)';
    $module_category_media_filter_element = $page->find('css', $module_category_media_filter_selector);
    // Make sure the 'Media' module category filter is applied.
    $this->assertEquals('Media', $module_category_media_filter_element->find('css', '.filter-label')->getText());
    // Assert that only media and administration module categories are shown.
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
    ]);
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('23 Results');
  }

  /**
   * Tests paging through results.
   */
  public function testPaging(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      'Unwritten&:/',
      'Astronaut Simulator',
    ]);
    $this->assertPagerItems([]);
    $assert_session->pageTextContains('9 Results');

    $page->pressButton('Clear filters');
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
    ]);
    $this->assertPagerItems(['1', '2', '3', 'Next', 'Last']);
    $assert_session->pageTextContains('25 Results');
    $assert_session->elementExists('css', '.pager__item--active > .is-active[aria-label="Page 1"]');

    $page->find('css', '[aria-label="Next page"]')->click();
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Ruh roh',
      'Fire',
      'Looper',
      'Grapefruit',
      'Become a Banana',
      'Unwritten&:/',
      'Doomer',
    ]);
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3', 'Next', 'Last']);

    $page->find('css', '[aria-label="Next page"]')->click();
    $this->assertProjectsVisible([
      'Astronaut Simulator',
    ]);
    $this->assertPagerItems(['First', 'Previous', '1', '2', '3']);

    // Ensure that when the number of projects is even divisible by the number
    // shown on a page, the pager has the correct number of items.
    $page->find('css', '[aria-label="First page"]')->click();
    // Click 'Media' checkbox.
    $page->find('css', '#67')->click();
    // Click 'Commerce/Advertising' checkbox.
    $page->find('css', '#55')->click();
    // Click 'E-commerce' checkbox.
    $page->find('css', '#104')->click();
    $this->assertPagerItems(['1', '2', 'Next', 'Last']);
    $assert_session->pageTextContains('24 Results');

    $page->find('css', '[aria-label="Next page"]')->click();
    $this->assertPagerItems(['First', 'Previous', '1', '2']);
  }

  /**
   * Tests advanced filtering.
   */
  public function testAdvancedFiltering(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $page->pressButton('Recommended filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');

    $this->assertProjectsVisible([
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      'Unwritten&:/',
      'Astronaut Simulator',
    ]);

    $second_filter_selector = 'p.filters-applied:nth-child(3)';
    $second_filter_element = $page->find('css', $second_filter_selector);
    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Covered by a security policy', $second_filter_element->find('css', '.filter-label')->getText());
    // Clear the security covered filter.
    $this->click("$second_filter_selector > button");
    $this->assertProjectsVisible([
      'Jazz',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
    ]);

    // Open the advanced filtering section.
    $filter_icon_selector = $page->find('css', '.advanced-filter-btn');
    $filter_icon_selector->click();

    // Click the Active filter.
    $element = $page->find('css', '#developmentStatusactive');
    $element->click();
    // Make sure the correct filter was applied.
    $second_filter_selector = 'p.filters-applied:nth-child(2)';
    $second_filter_element = $page->find('css', $second_filter_selector);
    $this->assertEquals('Active', $second_filter_element->find('css', '.filter-label')->getText());
    $this->assertProjectsVisible([
      'Jazz',
      'Cream cheese on a bagel',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Become a Banana',
      'Astronaut Simulator',
    ]);

    // Click the "Show all" filter for security.
    $not_covered_element = $page->find('css', '#securityCoverageall');
    $not_covered_element->click();
    $this->assertProjectsVisible([
      'Jazz',
      'Cream cheese on a bagel',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Octopus',
      'Helvetica',
      '1 Starts With a Number',
      'Become a Banana',
      'Astronaut Simulator',
    ]);

    // Clear all filters.
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('25 Results');

    // Click the Actively maintained filter.
    $element = $page->find('css', '#maintenanceStatusmaintained');
    $element->click();
    $first_filter_element = $page->find('css', 'p.filters-applied:nth-child(2)');
    $this->assertEquals('Maintained', $first_filter_element->find('css', '.filter-label')->getText());
    $this->assertProjectsVisible([
      'Jazz',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Dancing Queen',
      'Kangaroo',
      '9 Starts With a Higher Number',
      'Quiznos',
      'Octopus',
      'Helvetica',
    ]);
  }

  /**
   * Tests sorting criteria.
   */
  public function testSortingCriteria(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Clear filters.
    $this->drupalGet('admin/modules/browse');
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');

    // Select 'A-Z' sorting order.
    $this->click('#pb-sort');
    $this->click('#pb-sort > option:nth-child(2)');
    // Assert that the projects are listed in ascending order of their titles.
    $this->assertProjectsVisible([
      '1 Starts With a Number',
      '9 Starts With a Higher Number',
      'Astronaut Simulator',
      'Become a Banana',
      'Cream cheese on a bagel',
      'Dancing Queen',
      'Doomer',
      'Eggman',
      'Fire',
      'Grapefruit',
      'Helvetica',
      'Ice Ice',
    ]);

    // Select 'Z-A' sorting order.
    $this->click('#pb-sort');
    $this->click('#pb-sort > option:nth-child(3)');
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
      'Tooth Fairy',
      'Soup',
      'Ruh roh',
      'Quiznos',
      'Pinky and the Brain',
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Looper',
      'Kangaroo',
    ]);

    // Select 'Project Usage' option.
    $this->click('#pb-sort');
    $this->click('#pb-sort > option:nth-child(1)');
    // Assert that the projects are listed in descending order of their usage.
    $this->assertProjectsVisible([
      'Jazz',
      'Eggman',
      'Tooth Fairy',
      'Vitamin&C;$?',
      'Cream cheese on a bagel',
      'Pinky and the Brain',
      'Ice Ice',
      'No Scrubs',
      'Soup',
      'Mad About You',
      'Dancing Queen',
      'Kangaroo',
    ]);

    // Select 'Date Created (Most Recent)' option.
    $this->click('#pb-sort');
    $this->click('#pb-sort > option:nth-child(4)');
    // Assert that the projects are listed in descending order of their date of
    // creation.
    $this->assertProjectsVisible([
      '9 Starts With a Higher Number',
      'Helvetica',
      'Become a Banana',
      'Ice Ice',
      'Astronaut Simulator',
      'Grapefruit',
      'Fire',
      'Cream cheese on a bagel',
      'No Scrubs',
      'Soup',
      'Octopus',
      'Tooth Fairy',
    ]);
  }

  /**
   * Tests search with strings that need URI encoding.
   */
  public function testSearchForSpecialChar(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Clear filters.
    $this->drupalGet('admin/modules/browse');
    $page->pressButton('Clear filters');
    $search_field = $page->find('css', '#pb-text');
    // Fill in the search field.
    $search_field->setValue('&');
    // Wait just slightly longer because of debouncing.
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
    ]);

    // Fill in the search field.
    $search_field->setValue('');
    $search_field->setValue('n&');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
      'Unwritten&:/',
    ]);

    $search_field->setValue('');
    $search_field->setValue('$');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);

    $search_field->setValue('');
    $search_field->setValue('?');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);

    $search_field->setValue('');
    $search_field->setValue('/');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Unwritten&:/',
    ]);

    $search_field->setValue('');
    $search_field->setValue(':');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Unwritten&:/',
    ]);

    $search_field->setValue('');
    $search_field->setValue(';');
    $this->getSession()->wait(500);
    $this->assertProjectsVisible([
      'Vitamin&C;$?',
    ]);
  }

  /**
   * Tests that filtering, sorting, paging persists.
   */
  public function testPersistence(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/modules/browse');
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    // Open advanced filtering.
    $filter_icon_element = $page->find('css', '.advanced-filter-btn');
    $filter_icon_element->click();
    // Select the active development status filter.
    $active_development_status_element = $page->find('css', '#developmentStatusactive');
    $active_development_status_element->click();
    // Select the Commerce/Advertising filter.
    $page->find('css', '#55')->click();
    // Select the Media filter.
    $page->find('css', '#67')->click();
    // Select 'Z-A' sorting order.
    $this->click('#pb-sort');
    $this->click('#pb-sort > option:nth-child(3)');
    $assert_session->pageTextContains('16 Results');
    $this->assertProjectsVisible([
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Looper',
      'Kangaroo',
      'Jazz',
      'Grapefruit',
      'Fire',
      'Eggman',
      'Doomer',
      'Dancing Queen',
      'Cream cheese on a bagel',
    ]);
    $page->find('css', '[aria-label="Next page"]')->click();
    $this->assertProjectsVisible([
      'Become a Banana',
      'Astronaut Simulator',
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
    $this->getSession()->reload();
    // Should still be on second results page.
    $this->assertProjectsVisible([
      'Become a Banana',
      'Astronaut Simulator',
      '9 Starts With a Higher Number',
      '1 Starts With a Number',
    ]);
    $assert_session->pageTextContains('16 Results');
    $first_filter_element = $page->find('css', 'p.filters-applied:nth-child(2)');
    $second_filter_element = $page->find('css', 'p.filters-applied:nth-child(3)');
    $third_filter_element = $page->find('css', 'p.filters-applied:nth-child(4)');
    $this->assertEquals('Active', $first_filter_element->find('css', '.filter-label')->getText());
    $this->assertEquals('Commerce/Advertising', $second_filter_element->find('css', '.filter-label')->getText());
    $this->assertEquals('Media', $third_filter_element->find('css', '.filter-label')->getText());
    $page->find('css', '[aria-label="First page"]')->click();
    $this->assertProjectsVisible([
      'Octopus',
      'No Scrubs',
      'Mad About You',
      'Looper',
      'Kangaroo',
      'Jazz',
      'Grapefruit',
      'Fire',
      'Eggman',
      'Doomer',
      'Dancing Queen',
      'Cream cheese on a bagel',
    ]);
    $this->assertEquals('Active', $first_filter_element->find('css', '.filter-label')->getText());
    $this->assertEquals('Commerce/Advertising', $second_filter_element->find('css', '.filter-label')->getText());
    $this->assertEquals('Media', $third_filter_element->find('css', '.filter-label')->getText());
  }

  /**
   * Tests recommended filters.
   */
  public function testRecommendedFilter(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Clear filters.
    $this->drupalGet('admin/modules/browse');
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('25 Results');
    $page->pressButton('Recommended filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    // Check that the actively maintained tag is present.
    $first_filter_selector = 'p.filters-applied:nth-child(2)';
    $first_filter_element = $page->find('css', $first_filter_selector);
    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Maintained', $first_filter_element->find('css', '.filter-label')->getText());
    // Check that the security covered tag is present.
    $second_filter_selector = 'p.filters-applied:nth-child(3)';
    $second_filter_element = $page->find('css', $second_filter_selector);
    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Covered by a security policy', $second_filter_element->find('css', '.filter-label')->getText());
    $assert_session->pageTextContains('9 Results');
  }

}
