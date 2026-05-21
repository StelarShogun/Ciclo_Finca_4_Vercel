/**
 * Inventory page bootstrap — code-split modules loaded after DOM ready.
 *
 * Bundles: inventory-shared + classification + chrome (entry),
 * then inventory-modals, inventory-actions, inventory-filters, inventory-stock.
 */
import '../../shared/ajax-pagination.js';
import { initSidebarToggle, initViewSwitcher } from './inventory-chrome.js';

initSidebarToggle();
initViewSwitcher();

function hideInventoryLoadingOverlay() {
    const overlay = document.querySelector('.loading-spinner-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const [
            { initModals },
            { initInventoryActions },
            { initInventoryFilters },
            { initStockModal },
        ] = await Promise.all([
            import('./inventory-modals.js'),
            import('./inventory-actions.js'),
            import('./inventory-filters.js'),
            import('./inventory-stock.js'),
        ]);

        const results = await Promise.allSettled([
            initModals(),
            initInventoryActions(),
            initInventoryFilters(),
            initStockModal(),
        ]);

        results.forEach((result, index) => {
            if (result.status === 'rejected') {
                console.error('[inventory] init chunk failed', index, result.reason);
            }
        });
    } catch (err) {
        console.error('[inventory] bootstrap failed', err);
    } finally {
        hideInventoryLoadingOverlay();
    }
});
