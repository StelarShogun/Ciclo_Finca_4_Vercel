import { qs, fillSubcategoryOptions } from './inventory-shared.js';
import { initClassificationFilters } from './inventory-classification-filters.js';

export function initInventoryFilters() {
    const productSection = document.querySelector('.products-section');
    const loadingSpinner = document.querySelector('.loading-spinner-overlay');
    const filterForm = document.querySelector('.filter-form');

    if (productSection && loadingSpinner) {
        const hideLoadingOverlay = () => {
            loadingSpinner.style.display = 'none';
        };

        // Module chunks often load after window "load" — listener would never run.
        if (document.readyState === 'complete') {
            hideLoadingOverlay();
        } else {
            loadingSpinner.style.display = 'flex';
            window.addEventListener('load', hideLoadingOverlay, { once: true });
        }
    }

    if (filterForm) {
        const parentFilter = qs('#parent-category-filter');
        const subcategoryFilter = qs('#subcategory-filter');
        const classificationToggleBtn = qs('#toggle-classification-filters');
        const classificationPanel = qs('#classification-filters-panel');

        const classificationFilters = initClassificationFilters({
            filterForm,
            panel: classificationPanel,
        });

        const openClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) {
                return;
            }
            classificationPanel.hidden = false;
            classificationPanel.classList.add('is-open');
            classificationToggleBtn.setAttribute('aria-expanded', 'true');
        };

        const closeClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) {
                return;
            }
            classificationPanel.classList.remove('is-open');
            classificationPanel.hidden = true;
            classificationToggleBtn.setAttribute('aria-expanded', 'false');
        };

        if (parentFilter && subcategoryFilter) {
            const selectedFromData = subcategoryFilter.dataset.selected || '';
            fillSubcategoryOptions(subcategoryFilter, parentFilter.value, selectedFromData);
            parentFilter.addEventListener('change', () => {
                fillSubcategoryOptions(subcategoryFilter, parentFilter.value);
            });
        }

        if (classificationToggleBtn && classificationPanel) {
            classificationToggleBtn.addEventListener('click', async () => {
                const isOpen = classificationToggleBtn.getAttribute('aria-expanded') === 'true';
                if (isOpen) {
                    closeClassificationPanel();
                    return;
                }
                openClassificationPanel();
                await classificationFilters.onPanelOpen();
            });
        }

        filterForm.addEventListener('submit', () => {
            if (loadingSpinner) {
                loadingSpinner.style.display = 'flex';
            }
        });
    }
}
