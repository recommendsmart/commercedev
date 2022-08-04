import { writable } from 'svelte/store';

// Store for applied advanced filters.
const storedFilters = JSON.parse(sessionStorage.getItem('advancedFilter')) || {
  developmentStatus: '',
  maintenanceStatus: '',
  securityCoverage: ''
};
export const filters = writable(storedFilters);
filters.subscribe((val) => sessionStorage.setItem('advancedFilter', JSON.stringify(val)));

export const rowsCount = writable(0);

export const filtersVocabularies = writable({
  developmentStatus: JSON.parse(localStorage.getItem('pb.developmentStatus')) || [],
  maintenanceStatus: JSON.parse(localStorage.getItem('pb.maintenanceStatus')) || [],
  securityCoverage: JSON.parse(localStorage.getItem('pb.securityCoverage')) || []
});

// Store for applied category filters.
const storedModuleCategoryFilter = JSON.parse(sessionStorage.getItem('categoryFilter')) || [];
export const moduleCategoryFilter = writable(storedModuleCategoryFilter);
moduleCategoryFilter.subscribe((val) => sessionStorage.setItem('categoryFilter', JSON.stringify(val)));

// Store for module category vocabularies.
export const moduleCategoryVocabularies = writable(JSON.parse(localStorage.getItem('pb.moduleCategoryVocabularies')) || []);
moduleCategoryVocabularies.subscribe((val) => localStorage.setItem('pb.moduleCategoryVocabularies', JSON.stringify(val)));

// Store used to check if the page has loaded once already.
const storedIsFirstLoad = JSON.parse(sessionStorage.getItem('isFirstLoad')) === false ? JSON.parse(sessionStorage.getItem('isFirstLoad')) : true;
export const isFirstLoad = writable(storedIsFirstLoad);
isFirstLoad.subscribe((val) => sessionStorage.setItem('isFirstLoad', JSON.stringify(val)));

// Store the page the user is on.
const storedPage = JSON.parse(sessionStorage.getItem('page')) || 0;
export const page = writable(storedPage);
page.subscribe((val) => sessionStorage.setItem('page', JSON.stringify(val)));

// Store the current sort selected.
const storedSort = JSON.parse(sessionStorage.getItem('sort')) || 'usage_total';
export const sort = writable(storedSort);
sort.subscribe((val) => sessionStorage.setItem('sort', JSON.stringify(val)));
