import { qs, qsa, fillSubcategoryOptions, jsonHeaders, readJsonOrThrow } from './inventory-shared.js';

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
        const classificationContainer = qs('#classification-filters-container');

        const getSelectedClassificationMap = () => {
            const selected = {};
            qsa('select[name^="classifications["]', filterForm).forEach((select) => {
                const match = select.name.match(/^classifications\[(.+)\]$/);
                if (!match) return;
                selected[match[1]] = String(select.value || '');
            });
            return selected;
        };

        const renderClassificationFilters = (filters, selected = {}) => {
            if (!classificationContainer) return;
            const list = Array.isArray(filters) ? filters : [];
            classificationContainer.innerHTML = '';

            if (!list.length) {
                const empty = document.createElement('p');
                empty.className = 'form-text text-muted';
                empty.textContent = 'No hay clasificaciones disponibles para los filtros base actuales.';
                classificationContainer.appendChild(empty);
                return;
            }

            list.forEach((filter) => {
                const slug = String(filter?.slug || '').trim();
                if (!slug) return;
                const label = String(filter?.label || slug);
                const options = Array.isArray(filter?.options) ? filter.options : [];

                const wrap = document.createElement('div');
                wrap.className = 'filter-group';

                const fieldLabel = document.createElement('label');
                fieldLabel.setAttribute('for', `classification-filter-${slug}`);
                fieldLabel.textContent = label;

                const select = document.createElement('select');
                select.id = `classification-filter-${slug}`;
                select.name = `classifications[${slug}]`;

                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = 'Todos';
                select.appendChild(opt0);

                options.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = String(option?.value ?? '');
                    opt.textContent = String(option?.label ?? option?.value ?? '');
                    if (String(selected[slug] || '') === opt.value) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });

                wrap.appendChild(fieldLabel);
                wrap.appendChild(select);
                classificationContainer.appendChild(wrap);
            });
        };

        const openClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) return;
            classificationPanel.hidden = false;
            classificationPanel.classList.add('is-open');
            classificationToggleBtn.setAttribute('aria-expanded', 'true');
        };

        const closeClassificationPanel = () => {
            if (!classificationPanel || !classificationToggleBtn) return;
            classificationPanel.classList.remove('is-open');
            classificationPanel.hidden = true;
            classificationToggleBtn.setAttribute('aria-expanded', 'false');
        };

        const loadClassificationFiltersOnDemand = async () => {
            if (!classificationContainer) return;
            if (classificationContainer.dataset.loaded === '1') return;

            const endpoint = classificationContainer.dataset.endpoint;
            if (!endpoint) return;

            const params = new URLSearchParams();
            const formData = new FormData(filterForm);
            formData.forEach((value, key) => {
                if (typeof value !== 'string' || value.trim() === '') return;
                if (key.startsWith('classifications[')) return;
                params.append(key, value);
            });

            classificationContainer.innerHTML = '<p class="form-text text-muted">Cargando clasificaciones…</p>';
            const url = params.toString() ? `${endpoint}?${params.toString()}` : endpoint;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: jsonHeaders(),
                });
                const data = await readJsonOrThrow(response, 'No se pudieron cargar las clasificaciones.');
                renderClassificationFilters(data?.filters || [], getSelectedClassificationMap());
                classificationContainer.dataset.loaded = '1';
            } catch (_err) {
                classificationContainer.innerHTML = '<p class="form-text text-muted" style="color:#b91c1c;">No se pudieron cargar los filtros de clasificación.</p>';
            }
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
                await loadClassificationFiltersOnDemand();
            });
        }

        filterForm.addEventListener('submit', () => {
            if (loadingSpinner) {
                loadingSpinner.style.display = 'flex';
            }
        });

    }
}
