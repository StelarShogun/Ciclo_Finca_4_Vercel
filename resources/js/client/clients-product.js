import { initClientProductPage } from './bundles/product.js';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('product-quantity') && !document.getElementById('carousel-track')) return;
  initClientProductPage();
});
