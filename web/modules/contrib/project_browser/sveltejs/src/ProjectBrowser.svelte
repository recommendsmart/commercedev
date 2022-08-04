<script>
  import { onMount } from 'svelte';
  import { withPrevious } from 'svelte-previous';
  import ProjectGrid, {
    Pagination,
    Search,
    Filter,
  } from './ProjectGrid.svelte';
  import Project from './Project/Project.svelte';
  import {
    filters,
    rowsCount,
    moduleCategoryFilter,
    isFirstLoad,
    page,
    sort,
  } from './stores';
  import MediaQuery from './MediaQuery.svelte';
  import {
    ACTIVELY_MAINTAINED_ID,
    COVERED_ID,
    ALL_VALUES_ID,
  } from './constants';

  const { drupalSettings, Drupal } = window;

  let data;
  let rows = [];
  const pageIndex = 0; // first row
  const pageSize = 12;

  let loading = true;
  let text = '';
  let toggleView = 'Grid';
  const [currentPage, previousPage] = withPrevious(0);
  $: $currentPage = $page;

  /**
   * Load data from Drupal.org API.
   *
   * @param {number|string} _page
   *   The page number.
   *
   * @return {Promise<void>}
   *   Empty promise that resolves on content load.*
   */
  async function load(_page) {
    loading = true;
    const searchParams = new URLSearchParams({
      page: _page,
      limit: pageSize,
      sort: $sort,
    });
    if (text) {
      searchParams.set('search', text);
    }
    if ($moduleCategoryFilter && $moduleCategoryFilter.length) {
      searchParams.set('categories', $moduleCategoryFilter);
    }
    if ($filters.developmentStatus && $filters.developmentStatus.length) {
      searchParams.set('development_status', $filters.developmentStatus);
    }
    if ($filters.maintenanceStatus && $filters.maintenanceStatus.length) {
      searchParams.set('maintenance_status', $filters.maintenanceStatus);
    }
    if ($filters.securityCoverage && $filters.securityCoverage.length) {
      searchParams.set('security_advisory_coverage', $filters.securityCoverage);
    }
    const url = `${
      drupalSettings.project_browser.origin_url
    }/drupal-org-proxy/project?${searchParams.toString()}`;

    const res = await fetch(url);
    if (res.ok) {
      data = await res.json();
      rows = data.list;
      $rowsCount = data.totalResults;
    } else {
      rows = [];
      $rowsCount = 0;
    }
    loading = false;
  }

  async function filterRecommended() {
    // Show recommended projects on initial page load only when no filters are applied.
    if (
      $filters.developmentStatus.length === 0 &&
      $filters.maintenanceStatus.length === 0 &&
      $filters.securityCoverage.length === 0
    ) {
      $filters.maintenanceStatus = ACTIVELY_MAINTAINED_ID;
      $filters.securityCoverage = COVERED_ID;
      $filters.developmentStatus = ALL_VALUES_ID;
    }
    isFirstLoad.set(false);
  }

  /**
   * Load remote data when the Svelte component is mounted.
   */
  onMount(async () => {
    // Only filter by recommended on first page load.
    if ($isFirstLoad) {
      await filterRecommended();
    }
    await load($page);
  });

  function onPageChange(event) {
    page.set(event.detail.page);
    load($page);
  }

  async function onSearch(event) {
    text = event.detail.text;
    await load(0);
    page.set(0);
  }

  async function onSelectCategory(event) {
    moduleCategoryFilter.set(event.detail.category);
    await load(0);
    page.set(0);
  }
  async function onSort(event) {
    sort.set(event.detail.sort);
    await load(0);
    page.set(0);
  }
  async function onAdvancedFilter(event) {
    $filters.developmentStatus = event.detail.developmentStatus;
    $filters.maintenanceStatus = event.detail.maintenanceStatus;
    $filters.securityCoverage = event.detail.securityCoverage;

    await load(0);
    page.set(0);
  }

  async function onToggle(val) {
    if (val !== toggleView) toggleView = val;
  }

  document.onmouseover = function setInnerDocClickTrue() {
    window.innerDocClick = true;
  };

  document.onmouseleave = function setInnerDocClickFalse() {
    window.innerDocClick = false;
  };

  // Handles back button functionality to go back to the previous page the user was on before.
  window.addEventListener('popstate', () => {
    // Confirm the popstate event was a back button action by checking that
    // the user clicked out of the document.
    if (!window.innerDocClick) {
      page.set($previousPage);
      load($page);
    }
  });

  // Removes initial loader if it exists.
  const initialLoader = document.getElementById('initial-loader');
  if (initialLoader) {
    initialLoader.remove();
  }
</script>

<MediaQuery query="(min-width: 1200px)" let:matches>
  <ProjectGrid {loading} {rows} {pageIndex} {pageSize} let:rows>
    <div slot="top">
      <Search
        on:search={onSearch}
        on:sort={onSort}
        on:advancedFilter={onAdvancedFilter}
        on:selectCategory={onSelectCategory}
      />
      {#if matches}
        <div class="toggle-buttons">
          <button
            class:selected={toggleView === 'List'}
            class="toggle list-button"
            value="List"
            on:click={(e) => {
              toggleView = 'List';
              onToggle(e.target.value);
            }}
          >
            {Drupal.t('List')}
          </button>
          <button
            class:selected={toggleView === 'Grid'}
            class="toggle grid-button"
            value="Grid"
            on:click={(e) => {
              toggleView = 'Grid';
              onToggle(e.target.value);
            }}
          >
            {Drupal.t('Grid')}
          </button>
        </div>
      {/if}
    </div>

    <div slot="left">
      <Filter on:selectCategory={onSelectCategory} />
    </div>
    {#each rows as row, index (row)}
      <Project toggleView={!matches ? 'Grid' : toggleView} project={row} />
    {/each}
    <div slot="bottom">
      <Pagination
        page={$page}
        {pageSize}
        count={$rowsCount}
        serverSide={true}
        on:pageChange={onPageChange}
      />
    </div>
  </ProjectGrid>
</MediaQuery>

<style>
  .toggle {
    margin-bottom: 1.5em;
    font-family: inherit;
    color: #232429;
    background-color: #d3d4d9;
    width: 80.41px;
    height: 30px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
    border: none;
  }

  .toggle:first-child {
    margin-left: auto;
  }
  .toggle-buttons {
    display: flex;
    margin-right: 25px;
  }
  .toggle.list-button {
    margin-right: 5px;
    border-radius: 2px 0 0 2px;
  }
  .toggle.grid-button {
    border-radius: 0 2px 2px 0;
  }
  .selected {
    background-color: #adaeb3;
  }
</style>
