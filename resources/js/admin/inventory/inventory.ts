// @ts-nocheck
/**
 * Inventory page bootstrap — load only what FCP needs synchronously.
 *
 * Initial chunks: shared utils + classification helpers + chrome (sidebar/view toggle),
 * filters, actions. Modals + stock modal are deferred until the user clicks a
 * trigger (or the browser is idle).
 */
import '../../shared/ajax-pagination';
import { initCatalogExportMenu, initSidebarToggle, initViewSwitcher } from './inventory-chrome';

initSidebarToggle();
initViewSwitcher();
initCatalogExportMenu();

function hideInventoryLoadingOverlay() {
    const overlay = document.querySelector('.loading-spinner-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Selectors that should trigger the heavy modal bundle.
const MODAL_TRIGGER_SELECTORS = [
    '#open-new-product-modal',
    '#open-new-category-modal',
    '#open-new-subcategory-modal',
    '#open-import-modal',
    '[data-edit-product]',
    '[data-open-product-modal]',
    '.edit-product-btn',
    '.edit-btn',
    '.view-details-btn',
    '.js-edit-variant',
    '.js-delete-variant',
    '.product-row',
    '.product-grid-card',
].join(',');

const STOCK_TRIGGER_SELECTOR = '[data-stock-action="add"], [data-stock-action="remove"]';

let modalsPromise = null;
let modalsReady = false;

function ensureModals() {
    if (!modalsPromise) {
        modalsPromise = import('./inventory-modals')
            .then(async (mod) => {
                if (typeof mod.initModals === 'function') {
                    await mod.initModals();
                }
                modalsReady = true;
                return mod;
            })
            .catch((err) => {
                console.error('[inventory] modals load failed', err);
                modalsPromise = null;
                throw err;
            });
    }
    return modalsPromise;
}

let stockPromise = null;
function ensureStockModal() {
    if (!stockPromise) {
        stockPromise = import('./inventory-stock')
            .then(async (mod) => {
                if (typeof mod.initStockModal === 'function') {
                    await mod.initStockModal();
                }
                return mod;
            })
            .catch((err) => {
                console.error('[inventory] stock modal load failed', err);
                stockPromise = null;
                throw err;
            });
    }
    return stockPromise;
}

function attachLazyTriggers() {
    document.addEventListener(
        'click',
        (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;

            if (target.closest(STOCK_TRIGGER_SELECTOR)) {
                void ensureStockModal();
            }

            const modalTrigger = target.closest(MODAL_TRIGGER_SELECTORS);
            if (modalTrigger) {
                if (!modalsReady) {
                    event.preventDefault();
                    event.stopPropagation();
                    void ensureModals().then(() => {
                        modalTrigger.dispatchEvent(
                            new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                        );
                    });
                } else {
                    void ensureModals();
                }
            }
        },
        true, // capture so we win before delegated handlers inside the chunks
    );
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const [{ initInventoryActions }, { initInventoryFilters }] = await Promise.all([
            import('./inventory-actions'),
            import('./inventory-filters'),
        ]);

        const results = await Promise.allSettled([
            initInventoryActions(),
            initInventoryFilters(),
        ]);

        results.forEach((result, index) => {
            if (result.status === 'rejected') {
                console.error('[inventory] init chunk failed', index, result.reason);
            }
        });

        attachLazyTriggers();

        // Warm up heavy modal chunk on idle so first open feels instant,
        // but never block FCP/LCP on it.
        const warm = () => {
            void ensureModals();
            void ensureStockModal();
        };
        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(warm, { timeout: 3000 });
        } else {
            setTimeout(warm, 1500);
        }
    } catch (err) {
        console.error('[inventory] bootstrap failed', err);
    } finally {
        hideInventoryLoadingOverlay();
    }
});
