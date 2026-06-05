// @ts-nocheck
document.addEventListener('DOMContentLoaded', () => {
    const parentSelect = document.getElementById('parent_category_id');
    const hintBox = document.getElementById('parent-subcategories-hint');
    const treeEl = document.getElementById('subcategories-by-parent-data');
    if (!parentSelect || !hintBox || !treeEl) {
        return;
    }

    let tree = {};
    try {
        tree = JSON.parse(treeEl.textContent || '{}');
    } catch {
        tree = {};
    }

    function renderSubcategories() {
        const parentId = parentSelect.value;

        if (!parentId) {
            hintBox.innerHTML = '<p>Selecciona una categoría padre para ver sus subcategorías actuales.</p>';
            return;
        }

        const key = String(parentId);
        const num = Number(parentId);
        let subs = tree[key] || tree[parentId] || (Number.isFinite(num) ? tree[num] : []) || [];

        if (!subs.length) {
            for (const k of Object.keys(tree)) {
                if (String(k) === key || Number(k) === num) {
                    subs = tree[k] || [];
                    break;
                }
            }
        }

        if (!subs.length) {
            hintBox.innerHTML = '<p>No hay subcategorías registradas para esta categoría padre.</p>';
            return;
        }

        const items = subs.map((sub) => `<li>${sub.name}</li>`).join('');
        hintBox.innerHTML = `<p>Subcategorías existentes:</p><ul>${items}</ul>`;
    }

    parentSelect.addEventListener('change', renderSubcategories);
    renderSubcategories();

    const pagination = document.querySelector('.category-hierarchy-pagination .pagination');
    if (!pagination) {
        return;
    }

    const goInput = pagination.querySelector('#goToPageInput');
    const goBtn = pagination.querySelector('#goToPageBtn');
    const pageParam = 'hierarchy_page';

    pagination.querySelectorAll('.button[aria-label]').forEach((link) => {
        if (link.getAttribute('aria-disabled') === 'true') {
            link.addEventListener('click', (e) => e.preventDefault());
        }
    });

    function goToPage() {
        const totalSpan = pagination.querySelector('.button.button-primary');
        if (!totalSpan || !goInput) {
            return;
        }

        const parts = totalSpan.textContent.trim().split('/');
        const lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
        let target = parseInt(String(goInput.value || '1').trim(), 10);
        if (Number.isNaN(target)) {
            target = 1;
        }
        target = Math.min(Math.max(1, target), lastPage);

        const url = new URL(window.location.href);
        url.searchParams.set(pageParam, String(target));
        window.location.assign(url.toString());
    }

    goBtn?.addEventListener('click', goToPage);
    goInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            goToPage();
        }
    });
});
