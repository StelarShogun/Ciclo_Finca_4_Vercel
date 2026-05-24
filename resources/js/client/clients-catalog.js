import { initClientCatalogPage } from './bundles/catalog.js';
import { startCatalogHeartbeat } from './clients-catalog-heartbeat.js';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('filter-form') && !document.querySelector('[data-catalog-spotlight-carousel]')) return;
  initClientCatalogPage();
  startCatalogHeartbeat();
});
