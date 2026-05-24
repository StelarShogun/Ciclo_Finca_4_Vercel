import { qs, jsonHeaders, readJsonOrThrow } from './inventory-shared.js';
import { createDropdownPortal } from '../shared/combobox-dropdown-portal.js';

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function debounce(fn, ms) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}

export function initClassificationFilters({ filterForm, panel }) {
    const root = qs('#classification-filters-root');
    if (!root || !filterForm) {
        return { onPanelOpen() {} };
    }

    const activeEl = qs('#classification-filters-active', root);
    const hiddenInputsEl = qs('#classification-filters-hidden-inputs', root);
    const dimensionPicker = qs('#classification-dimension-picker', root);
    const valueSearch = qs('#classification-value-search', root);
    const valueList = qs('#classification-value-list', root);
    const comboboxWrap = qs('.classification-filter-combobox', root);

    const dimensionsEndpoint = root.dataset.endpointDimensions || '';
    const suggestTemplate = root.dataset.endpointSuggestTemplate || '';

    /** @type {Map<string, { slug: string, dimensionLabel: string, value: string, valueLabel: string }>} */
    const activeFilters = new Map();
    let dimensions = [];
    let suggestAbort = null;
    let open = false;

    const listPortal = createDropdownPortal(valueSearch || comboboxWrap, valueList);

    try {
        const initial = JSON.parse(root.dataset.initial || '[]');
        if (Array.isArray(initial)) {
            initial.forEach((filter) => {
                const slug = String(filter?.slug || '').trim();
                const value = String(filter?.value || '').trim();
                if (!slug || !value) {
                    return;
                }
                activeFilters.set(slug, {
                    slug,
                    dimensionLabel: String(filter?.dimension_label || slug),
                    value,
                    valueLabel: String(filter?.value_label || value),
                });
            });
        }
    } catch (_err) {
        // Ignore malformed SSR payload.
    }

    function syncHiddenInputs() {
        if (!hiddenInputsEl) {
            return;
        }
        hiddenInputsEl.innerHTML = '';
        activeFilters.forEach((filter) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `classifications[${filter.slug}]`;
            input.value = filter.value;
            hiddenInputsEl.appendChild(input);
        });
    }

    function renderActiveChips() {
        if (!activeEl) {
            return;
        }
        activeEl.innerHTML = '';
        if (activeFilters.size === 0) {
            activeEl.hidden = true;
            return;
        }
        activeEl.hidden = false;

        activeFilters.forEach((filter, slug) => {
            const chip = document.createElement('span');
            chip.className = 'classification-filter-chip cf-chip';
            chip.innerHTML = `${escapeHtml(filter.dimensionLabel)}: ${escapeHtml(filter.valueLabel)}`;

            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'cf-chip__clear';
            clearBtn.setAttribute('aria-label', `Quitar filtro ${filter.dimensionLabel}`);
            clearBtn.innerHTML = '&times;';
            clearBtn.addEventListener('click', () => {
                activeFilters.delete(slug);
                renderActiveChips();
                syncHiddenInputs();
                updateDimensionPicker();
            });

            chip.appendChild(clearBtn);
            activeEl.appendChild(chip);
        });
    }

    function buildScopeParams() {
        const params = new URLSearchParams();
        const formData = new FormData(filterForm);
        formData.forEach((value, key) => {
            if (typeof value !== 'string' || value.trim() === '') {
                return;
            }
            if (key.startsWith('classifications[')) {
                return;
            }
            params.append(key, value);
        });

        activeFilters.forEach((filter, slug) => {
            params.append(`classifications[${slug}]`, filter.value);
        });

        return params;
    }

    function suggestUrl(slug) {
        return suggestTemplate.replace('__SLUG__', encodeURIComponent(slug));
    }

    function closeSuggestions() {
        open = false;
        if (valueList) {
            valueList.hidden = true;
        }
        comboboxWrap?.classList.remove('is-dropdown-open');
        listPortal.unmount();
    }

    function updateBuilderState() {
        const hasDimension = Boolean(dimensionPicker?.value);
        if (valueSearch) {
            valueSearch.disabled = !hasDimension;
        }
    }

    function updateDimensionPicker() {
        if (!dimensionPicker) {
            return;
        }

        const current = dimensionPicker.value;
        dimensionPicker.innerHTML = '<option value="">Elegir atributo…</option>';

        dimensions.forEach((dimension) => {
            const slug = String(dimension?.slug || '').trim();
            if (!slug) {
                return;
            }
            const option = document.createElement('option');
            option.value = slug;
            option.textContent = String(dimension?.label || slug);
            if (activeFilters.has(slug)) {
                option.textContent = `${option.textContent} (reemplazar)`;
            }
            dimensionPicker.appendChild(option);
        });

        dimensionPicker.disabled = dimensions.length === 0;
        if (current && dimensions.some((dimension) => dimension.slug === current)) {
            dimensionPicker.value = current;
        } else {
            dimensionPicker.value = '';
        }

        updateBuilderState();
    }

    function renderSuggestionList(options) {
        if (!valueList) {
            return;
        }

        valueList.innerHTML = '';
        options.forEach((option) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cf-list__item';
            btn.textContent = String(option?.label ?? option?.value ?? '');
            btn.addEventListener('mousedown', (event) => event.preventDefault());
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                applyFilter(option);
            });
            valueList.appendChild(btn);
        });

        const visible = open && options.length > 0;
        valueList.hidden = !visible;
        comboboxWrap?.classList.toggle('is-dropdown-open', visible);
        if (visible) {
            listPortal.mount();
        } else {
            listPortal.unmount();
        }
    }

    async function fetchSuggestions() {
        const slug = dimensionPicker?.value;
        if (!slug || !valueSearch) {
            return;
        }

        if (suggestAbort) {
            suggestAbort.abort();
        }
        suggestAbort = new AbortController();

        const params = buildScopeParams();
        params.delete(`classifications[${slug}]`);

        const query = valueSearch.value.trim();
        if (query) {
            params.set('q', query);
        }
        params.set('limit', '50');

        try {
            const response = await fetch(`${suggestUrl(slug)}?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: jsonHeaders(),
                signal: suggestAbort.signal,
            });
            const data = await readJsonOrThrow(response, 'No se pudieron cargar sugerencias.');
            renderSuggestionList(Array.isArray(data?.options) ? data.options : []);
        } catch (error) {
            if (error?.name !== 'AbortError') {
                renderSuggestionList([]);
            }
        }
    }

    const debouncedFetchSuggestions = debounce(fetchSuggestions, 200);

    function applyFilter(option) {
        const slug = dimensionPicker?.value;
        if (!slug) {
            return;
        }

        const dimension = dimensions.find((item) => item.slug === slug);
        activeFilters.set(slug, {
            slug,
            dimensionLabel: String(dimension?.label || slug),
            value: String(option?.value ?? ''),
            valueLabel: String(option?.label ?? option?.value ?? ''),
        });

        renderActiveChips();
        syncHiddenInputs();
        if (valueSearch) {
            valueSearch.value = '';
        }
        closeSuggestions();
        updateDimensionPicker();
    }

    async function loadDimensions() {
        if (!dimensionsEndpoint || !dimensionPicker) {
            return;
        }

        dimensionPicker.disabled = true;
        dimensionPicker.innerHTML = '<option value="">Cargando…</option>';

        const params = buildScopeParams();
        activeFilters.forEach((_filter, slug) => {
            params.delete(`classifications[${slug}]`);
        });

        const url = params.toString() ? `${dimensionsEndpoint}?${params.toString()}` : dimensionsEndpoint;

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: jsonHeaders(),
            });
            const data = await readJsonOrThrow(response, 'No se pudieron cargar atributos.');
            dimensions = Array.isArray(data?.dimensions) ? data.dimensions : [];
            updateDimensionPicker();
        } catch (_error) {
            dimensions = [];
            dimensionPicker.innerHTML = '<option value="">Error al cargar</option>';
            dimensionPicker.disabled = true;
            if (valueSearch) {
                valueSearch.disabled = true;
            }
        }
    }

    dimensionPicker?.addEventListener('change', () => {
        if (valueSearch) {
            valueSearch.value = '';
        }
        closeSuggestions();
        updateBuilderState();
    });

    valueSearch?.addEventListener('focus', () => {
        open = true;
        debouncedFetchSuggestions();
    });

    valueSearch?.addEventListener('input', () => {
        open = true;
        debouncedFetchSuggestions();
    });

    valueSearch?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSuggestions();
            return;
        }

        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        const firstItem = valueList?.querySelector('.cf-list__item');
        if (firstItem) {
            firstItem.click();
        }
    });

    document.addEventListener('click', (event) => {
        if (!panel?.contains(event.target)) {
            closeSuggestions();
        }
    });

    renderActiveChips();
    syncHiddenInputs();

    const onPanelOpen = async () => {
        if (valueSearch) {
            valueSearch.value = '';
        }
        closeSuggestions();
        await loadDimensions();
    };

    if (panel && !panel.hidden) {
        void onPanelOpen();
    }

    return { onPanelOpen };
}
