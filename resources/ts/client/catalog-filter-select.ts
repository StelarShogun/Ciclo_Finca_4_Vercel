// @ts-nocheck
/**
 * Custom filter dropdowns (replaces native select in catalog filters — mobile-safe).
 */

function syncFilterSelectMenuPosition(root, menu, trigger) {
    if (!root || !menu || !trigger) {
        return;
    }

    if (window.innerWidth > 1024) {
        menu.style.removeProperty('position');
        menu.style.removeProperty('top');
        menu.style.removeProperty('left');
        menu.style.removeProperty('right');
        menu.style.removeProperty('width');
        menu.style.removeProperty('max-height');
        menu.style.removeProperty('z-index');
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const gap = 6;
    const side = Math.max(12, Math.round(rect.left));
    const width = Math.min(Math.round(rect.width), window.innerWidth - side - 12);
    const spaceBelow = window.innerHeight - rect.bottom - gap - 12;
    const maxHeight = Math.max(120, Math.min(280, Math.round(spaceBelow)));

    menu.style.position = 'fixed';
    menu.style.top = `${Math.round(rect.bottom + gap)}px`;
    menu.style.left = `${side}px`;
    menu.style.right = 'auto';
    menu.style.width = `${Math.max(width, 200)}px`;
    menu.style.maxHeight = `${maxHeight}px`;
    menu.style.zIndex = '1205';
}

function initCatalogFilterSelect(root) {
    const nativeSelect = root.querySelector('.catalog-filter-select__native');
    const trigger = root.querySelector('.catalog-filter-select__trigger');
    const menu = root.querySelector('.catalog-filter-select__menu');
    const label = root.querySelector('.catalog-filter-select__label');
    const options = menu ? Array.from(menu.querySelectorAll('.catalog-filter-select__option')) : [];

    if (!nativeSelect || !trigger || !menu || !label || options.length === 0) {
        return;
    }

    root.classList.add('is-enhanced');

    const close = () => {
        root.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
        menu.style.removeProperty('position');
        menu.style.removeProperty('top');
        menu.style.removeProperty('left');
        menu.style.removeProperty('right');
        menu.style.removeProperty('width');
        menu.style.removeProperty('max-height');
        menu.style.removeProperty('z-index');
    };

    const open = () => {
        root.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
        syncFilterSelectMenuPosition(root, menu, trigger);
    };

    const setValue = (value, text) => {
        nativeSelect.value = value;
        label.textContent = text;
        options.forEach((option) => {
            const isActive = option.getAttribute('data-value') === value;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (root.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    });

    options.forEach((option) => {
        option.addEventListener('click', (event) => {
            event.preventDefault();
            setValue(option.getAttribute('data-value') ?? '', option.textContent.trim());
            close();
            trigger.focus();
        });
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            close();
            trigger.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (root.classList.contains('is-open')) {
            syncFilterSelectMenuPosition(root, menu, trigger);
        }
    });

    window.addEventListener(
        'scroll',
        () => {
            if (root.classList.contains('is-open')) {
                syncFilterSelectMenuPosition(root, menu, trigger);
            }
        },
        true,
    );
}

export function initCatalogFilterSelects() {
    document.querySelectorAll('[data-catalog-filter-select]').forEach((root) => {
        initCatalogFilterSelect(root);
    });
}
