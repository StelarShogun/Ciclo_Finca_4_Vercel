/**
 * Searchable static combobox for admin forms (brands, suppliers, categories, etc.).
 */

const comboboxInstances = new Set();
let documentClickBound = false;

function bindDocumentClickClose() {
    if (documentClickBound) return;
    documentClickBound = true;
    document.addEventListener('click', (e) => {
        comboboxInstances.forEach((instance) => {
            if (!instance.wrapper.contains(e.target)) {
                instance.restoreLabelIfInvalid();
                instance.close();
            }
        });
    });
}

export function initStaticSearchCombobox({
    searchInputId,
    hiddenInputId,
    dropdownId,
    wrapperId,
    options: initialOptions = [],
    getId = (o) => o.id,
    getLabel = (o) => o.name,
    placeholder = '',
    noResultsText = 'Sin resultados',
    onSelected,
}) {
    const searchInput = document.getElementById(searchInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const dropdown = document.getElementById(dropdownId);
    const wrapper = document.getElementById(wrapperId);
    const chevron = wrapper?.querySelector('.brand-combobox-chevron');

    if (!searchInput || !hiddenInput || !dropdown || !wrapper) {
        return null;
    }

    let options = Array.isArray(initialOptions) ? initialOptions.slice() : [];
    let isOpen = false;
    let activeIndex = -1;
    const changeListeners = [];

    if (placeholder) {
        searchInput.setAttribute('placeholder', placeholder);
    }

    searchInput.setAttribute('role', 'combobox');
    searchInput.setAttribute('aria-autocomplete', 'list');
    searchInput.setAttribute('aria-controls', dropdownId);
    dropdown.setAttribute('role', 'listbox');

    function normalizedId(value) {
        return value === null || value === undefined ? '' : String(value);
    }

    function findOptionById(id) {
        const needle = normalizedId(id);
        if (!needle) return null;
        return options.find((o) => normalizedId(getId(o)) === needle) || null;
    }

    function setAriaExpanded(open) {
        searchInput.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function emitChange() {
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        changeListeners.forEach((fn) => fn(hiddenInput.value));
    }

    function getFiltered(query) {
        const q = (query || '').toLowerCase().trim();
        return q ? options.filter((o) => getLabel(o).toLowerCase().includes(q)) : options.slice();
    }

    function renderOptions(query) {
        const filtered = getFiltered(query);
        dropdown.innerHTML = '';
        activeIndex = -1;
        searchInput.removeAttribute('aria-activedescendant');

        if (!filtered.length) {
            const noResult = document.createElement('div');
            noResult.className = 'brand-combobox-no-result';
            noResult.setAttribute('role', 'status');
            noResult.textContent = noResultsText;
            dropdown.appendChild(noResult);
            return;
        }

        filtered.forEach((option, index) => {
            const item = document.createElement('div');
            const optionId = `${dropdownId}-opt-${index}`;
            item.id = optionId;
            item.className = 'brand-combobox-option';
            item.setAttribute('role', 'option');
            item.dataset.index = String(index);
            if (normalizedId(getId(option)) === normalizedId(hiddenInput.value)) {
                item.classList.add('selected');
                item.setAttribute('aria-selected', 'true');
            } else {
                item.setAttribute('aria-selected', 'false');
            }
            item.textContent = getLabel(option);
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectOption(option);
            });
            dropdown.appendChild(item);
        });
    }

    function highlightActive() {
        const items = dropdown.querySelectorAll('.brand-combobox-option');
        items.forEach((el, i) => {
            const selected = i === activeIndex;
            el.classList.toggle('selected', selected);
            el.setAttribute('aria-selected', selected ? 'true' : 'false');
            if (selected) {
                searchInput.setAttribute('aria-activedescendant', el.id);
            }
        });
        const active = items[activeIndex];
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        } else {
            searchInput.removeAttribute('aria-activedescendant');
        }
    }

    function selectOption(option) {
        hiddenInput.value = normalizedId(getId(option));
        searchInput.value = getLabel(option);
        wrapper.classList.remove('error');
        close();
        emitChange();
        if (typeof onSelected === 'function') {
            onSelected(option);
        }
    }

    function open() {
        if (searchInput.disabled) return;
        renderOptions(searchInput.value);
        dropdown.classList.add('open');
        wrapper.classList.add('open');
        isOpen = true;
        setAriaExpanded(true);
        if (chevron) chevron.classList.add('rotated');
    }

    function close() {
        dropdown.classList.remove('open');
        wrapper.classList.remove('open');
        isOpen = false;
        activeIndex = -1;
        setAriaExpanded(false);
        searchInput.removeAttribute('aria-activedescendant');
        if (chevron) chevron.classList.remove('rotated');
    }

    function reset() {
        hiddenInput.value = '';
        searchInput.value = '';
        wrapper.classList.remove('error');
        close();
    }

    function setValue(id, { silent = false } = {}) {
        const option = findOptionById(id);
        if (option) {
            hiddenInput.value = normalizedId(getId(option));
            searchInput.value = getLabel(option);
            wrapper.classList.remove('error');
            close();
            if (!silent) emitChange();
            return;
        }
        reset();
    }

    function restoreLabelIfInvalid() {
        const option = findOptionById(hiddenInput.value);
        searchInput.value = option ? getLabel(option) : '';
        if (!option) {
            hiddenInput.value = '';
        }
    }

    function setOptions(newOptions, { disabled = false, placeholder: ph } = {}) {
        options = Array.isArray(newOptions) ? newOptions.slice() : [];
        if (ph) {
            searchInput.setAttribute('placeholder', ph);
        }
        searchInput.disabled = Boolean(disabled);
        wrapper.classList.toggle('is-disabled', Boolean(disabled));
        if (disabled) {
            reset();
            close();
        }
    }

    function setDisabled(disabled) {
        searchInput.disabled = Boolean(disabled);
        wrapper.classList.toggle('is-disabled', Boolean(disabled));
        if (disabled) {
            close();
        }
    }

    searchInput.addEventListener('focus', () => open());

    searchInput.addEventListener('input', () => {
        hiddenInput.value = '';
        if (!isOpen) open();
        else renderOptions(searchInput.value);
    });

    searchInput.addEventListener('blur', () => {
        setTimeout(() => {
            restoreLabelIfInvalid();
            close();
        }, 150);
    });

    searchInput.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.brand-combobox-option');
        if (e.key === 'Escape') {
            e.preventDefault();
            restoreLabelIfInvalid();
            close();
            return;
        }
        if (!isOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            open();
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!items.length) return;
            activeIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
            highlightActive();
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!items.length) return;
            activeIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
            highlightActive();
            return;
        }
        if (e.key === 'Enter') {
            if (!isOpen || activeIndex < 0 || !items[activeIndex]) return;
            e.preventDefault();
            const filtered = getFiltered(searchInput.value);
            const option = filtered[activeIndex];
            if (option) selectOption(option);
        }
    });

    if (chevron) {
        chevron.style.pointerEvents = 'auto';
        chevron.addEventListener('mousedown', (e) => {
            e.preventDefault();
            if (isOpen) close();
            else {
                searchInput.focus();
                open();
            }
        });
    }

    const instance = {
        wrapper,
        restoreLabelIfInvalid,
        close,
        getValue: () => hiddenInput.value,
        setValue,
        reset,
        open,
        setOptions,
        setDisabled,
        onChange(fn) {
            if (typeof fn === 'function') changeListeners.push(fn);
        },
        get element() {
            return hiddenInput;
        },
    };

    comboboxInstances.add(instance);
    bindDocumentClickClose();

    return instance;
}

/** @param {HTMLElement} wrapper */
export function setComboboxFieldError(wrapper, message = '') {
    if (!wrapper) return;
    const field = wrapper.closest('.form-group');
    wrapper.classList.toggle('error', Boolean(message));
    let err = field?.querySelector('.js-combobox-field-error');
    if (!field) return;
    if (!err) {
        err = document.createElement('p');
        err.className = 'field-error js-combobox-field-error';
        field.appendChild(err);
    }
    err.textContent = message || '';
}
