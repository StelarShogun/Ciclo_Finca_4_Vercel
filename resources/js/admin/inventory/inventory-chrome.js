import { qs, qsa } from './inventory-shared.js';

export function initSidebarToggle() {
    const btn = qs('#sidebarToggle');
    if (!btn) return;

    const BODY_COLLAPSED = 'sidebar-collapsed';
    const KEY = 'cp_sidebar_collapsed';

    const saved = localStorage.getItem(KEY);
    if (saved === '1') {
        document.body.classList.add(BODY_COLLAPSED);
        btn.classList.add('is-collapsed');
    }

    const setCollapsed = (collapsed) => {
        document.body.classList.toggle(BODY_COLLAPSED, collapsed);
        btn.classList.toggle('is-collapsed', collapsed);
        localStorage.setItem(KEY, collapsed ? '1' : '0');
    };

    btn.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains(BODY_COLLAPSED);
        setCollapsed(collapsed);
        btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    });
}

// View switcher for inventory list with persistence
export function initCatalogExportMenu() {
    const toggle = qs('#inventory-export-toggle');
    const menu = qs('#inventory-export-menu');
    if (!toggle || !menu) {
        return;
    }

    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = menu.hidden;
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== toggle) {
            close();
        }
    });
}

export function initViewSwitcher() {
    const viewButtons = qsa('.view-btn');
    const tableView = qs('.table-view');
    const gridView = qs('.grid-view');
    const KEY = 'cp_inventory_view';

    const savedView = localStorage.getItem(KEY);
    if (savedView) {
        setView(savedView);
    }

    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            setView(view);
            localStorage.setItem(KEY, view);
        });
    });

    function setView(view) {
        if (view === 'table') {
            tableView.classList.add('active');
            gridView.classList.remove('active');
            qs('.view-btn[data-view="table"]').classList.add('active');
            qs('.view-btn[data-view="grid"]').classList.remove('active');
        } else {
            tableView.classList.remove('active');
            gridView.classList.add('active');
            qs('.view-btn[data-view="table"]').classList.remove('active');
            qs('.view-btn[data-view="grid"]').classList.add('active');
        }
    }
}

