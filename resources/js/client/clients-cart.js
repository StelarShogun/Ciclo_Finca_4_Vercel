import { initClientCartPage } from './bundles/cart.js';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('proceed-checkout') && !document.querySelector('.cart-item')) return;
  initClientCartPage();
});
