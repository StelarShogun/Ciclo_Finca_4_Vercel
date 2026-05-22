import { initClientCatalogPage } from './bundles/catalog.js';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('filter-form') && !document.querySelector('[data-catalog-spotlight-carousel]')) return;
  initClientCatalogPage();
});
