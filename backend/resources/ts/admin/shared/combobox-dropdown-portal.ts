// @ts-nocheck
/**
 * Moves combobox / autocomplete panels to document.body with fixed positioning
 * so they are not clipped by modal overflow or form-section overflow.
 */

export function createDropdownPortal(anchorEl, panelEl) {
    if (!anchorEl || !panelEl) {
        return { mount() {}, unmount() {}, position() {} };
    }

    let placeholder = null;
    let scrollCleanups = [];

    function clearScrollListeners() {
        scrollCleanups.forEach((fn) => fn());
        scrollCleanups = [];
    }

    function position() {
        const rect = anchorEl.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom - 12;
        const spaceAbove = rect.top - 12;
        const preferredMax = 280;
        const maxHeight = Math.min(preferredMax, Math.max(spaceBelow, spaceAbove, 100));

        let top = rect.bottom + 4;
        if (spaceBelow < 100 && spaceAbove > spaceBelow) {
            top = Math.max(8, rect.top - maxHeight - 4);
        }

        panelEl.style.position = 'fixed';
        panelEl.style.left = `${Math.max(8, rect.left)}px`;
        panelEl.style.top = `${top}px`;
        panelEl.style.width = `${Math.min(rect.width, window.innerWidth - 16)}px`;
        panelEl.style.maxHeight = `${maxHeight}px`;
        panelEl.style.zIndex = '10050';
        panelEl.style.overflowY = 'auto';
    }

    function mount() {
        if (panelEl.dataset.portalMounted === '1') {
            position();
            return;
        }

        placeholder = document.createComment('dropdown-portal');
        panelEl.parentNode?.insertBefore(placeholder, panelEl);
        document.body.appendChild(panelEl);
        panelEl.dataset.portalMounted = '1';
        panelEl.classList.add('is-portal-dropdown');
        position();

        const onReposition = () => position();
        window.addEventListener('scroll', onReposition, true);
        scrollCleanups.push(() => window.removeEventListener('scroll', onReposition, true));

        const modalBody = anchorEl.closest('.modal-body');
        if (modalBody) {
            modalBody.addEventListener('scroll', onReposition);
            scrollCleanups.push(() => modalBody.removeEventListener('scroll', onReposition));
        }

        window.addEventListener('resize', onReposition);
        scrollCleanups.push(() => window.removeEventListener('resize', onReposition));
    }

    function unmount() {
        clearScrollListeners();
        if (panelEl.dataset.portalMounted !== '1') {
            return;
        }

        delete panelEl.dataset.portalMounted;
        panelEl.classList.remove('is-portal-dropdown');
        ['position', 'left', 'top', 'width', 'maxHeight', 'zIndex', 'overflowY'].forEach((prop) => {
            panelEl.style.removeProperty(prop);
        });

        if (placeholder?.parentNode) {
            placeholder.parentNode.insertBefore(panelEl, placeholder.nextSibling);
            placeholder.remove();
        }
        placeholder = null;
    }

    return { mount, unmount, position };
}
