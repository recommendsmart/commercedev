<?php

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Provides tests for the Project Browser Plugins.
 *
 * @group project_browser
 */
class ProjectBrowserPluginTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'project_browser',
    'project_browser_devel',
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
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
    ]));
  }

  /**
   * Tests the Random Data plugin.
   */
  public function testRandomDataPlugin(): void {
    $assert_session = $this->assertSession();

    $this->getSession()->resizeWindow(1200, 1200);
    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElement('css', '#project-browser .grid');
    $grid_text = $this->getSession()->getPage()->find('css', '#project-browser .toggle-buttons .grid-button')->getText();
    $this->assertEquals('Grid', $grid_text);
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextNotContains('No records available');
  }

  /**
   * Tests the available categories.
   */
  public function testCategories(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElement('css', '.pb-categories input[type="checkbox"]');
    $assert_session->elementsCount('css', '.pb-categories input[type="checkbox"]', 20);
  }

  /**
   * Tests paging through results.
   *
   * We want to click through things and make sure that things are functional.
   * We don't care about the number of results.
   */
  public function testPaging(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    $this->assertNotNull($assert_session->waitForElement('css', '.pager__item--next'));
    $assert_session->elementsCount('css', '.pager__item--next', 1);

    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    $assert_session->waitForElement('css', '.pager__item--next');
    $assert_session->elementsCount('css', '.pager__item--next', 1);

    $page->find('css', 'a[aria-label="Next page"]')->click();
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->elementsCount('css', '.pager__item--previous', 1);
  }

  /**
   * Tests advanced filtering.
   */
  public function testAdvancedFiltering(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    $second_filter_selector = 'p.filters-applied:last-of-type';
    $second_filter_element = $page->find('css', $second_filter_selector);
    // Make sure the second filter applied is the security covered filter.
    $this->assertEquals('Covered by a security policy', $second_filter_element->find('css', '.filter-label')->getText());
    // Clear the security covered filter.
    $this->click("$second_filter_selector > button");
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    $filter_icon_selector = $page->find('css', '.advanced-filter-btn');
    $filter_icon_selector->click();

    // Clear all filters.
    $page->pressButton('Clear filters');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
  }

  /**
   * Tests broken images.
   */
  public function testBrokenImages(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    // RandomData always give an image URL. Sometimes it is a fake URL on
    // purpose so it 404s. This check means that the original image was not
    // found and it was replaced by the placeholder.
    $assert_session->responseContains('puzzle-piece-placeholder.svg');
  }

  /**
   * Tests the not-compatible flag.
   */
  public function testNotCompatibleButton(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/modules/browse');
    $assert_session->waitForElementVisible('css', '#project-browser .project');
    $assert_session->pageTextContains('Results');
    $disabled_button = $page->find('css', '.button.is-disabled');
    $this->assertEquals('Not compatible', $disabled_button->getText());
  }

}
