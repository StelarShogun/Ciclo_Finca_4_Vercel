function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function esc(text) {
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function formatColones(value) {
    const n = Math.round(Number(value) || 0);
    return `₡${n.toLocaleString('es-CR')}`;
}

async function fetchProducts(q, supplierId) {
    const params = new URLSearchParams();
    params.set('q', q);
    params.set('supplier_id', String(supplierId));
    const res = await fetch(`/admin/products/search?${params.toString()}`, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
        credentials: 'same-origin',
    });
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = null;
    }

    if (!res.ok) {
        const msg = data?.message || `No se pudo buscar productos (HTTP ${res.status}).`;
        const err = new Error(msg);
        // @ts-ignore
        err.status = res.status;
        // @ts-ignore
        err.data = data;
        // @ts-ignore
        err.raw = text;
        throw err;
    }

    return data ?? { success: true, products: [] };
}

function init() {
    const form = document.getElementById('supplier-order-create-form');
    const supplierSelect = document.getElementById('supplier_id');
    const supplierPreview = document.getElementById('supplier-preview');
    const dateInput = document.getElementById('estimated_delivery_date');
    const searchInput = document.getElementById('product-search');
    const comboboxWrapper = document.getElementById('product-combobox');
    const comboboxDropdown = document.getElementById('product-search-dropdown');
    const tbody = document.getElementById('items-body');
    const summaryLines = document.getElementById('summary-lines');
    const summaryTotal = document.getElementById('summary-total');
    const itemsErrors = document.getElementById('items-errors');

    if (!form || !supplierSelect || !dateInput || !searchInput || !tbody || !summaryLines || !summaryTotal) return;

    const suppliers = Array.isArray(window.__CF4_SUPPLIERS__) ? window.__CF4_SUPPLIERS__ : [];

    /** @type {Array<{product_id:number,name:string,sku:string,unit_price:number,quantity:number}>} */
    let lines = [];

    /** @type {Array<{product_id:number,name:string,sku:string,unit_price:number}>} */
    let allProducts = [];

    function renderSupplierPreview() {
        const id = Number(supplierSelect.value || 0);
        const s = suppliers.find((x) => Number(x.supplier_id) === id);
        if (!s) {
            supplierPreview.hidden = true;
            supplierPreview.innerHTML = '';
            return;
        }
        supplierPreview.hidden = false;
        supplierPreview.innerHTML = `
            <div class="k">Contacto</div><div class="v">${esc(s.primary_contact || '—')}</div>
            <div class="k">Correo</div><div class="v">${esc(s.email || '—')}</div>
            <div class="k">Teléfono</div><div class="v">${esc(s.phone || '—')}</div>
        `;
    }

    function updateSummary() {
        summaryLines.textContent = String(lines.length);
        const total = lines.reduce((acc, l) => acc + (Number(l.unit_price) || 0) * (Number(l.quantity) || 0), 0);
        summaryTotal.textContent = formatColones(total);
    }

    function showItemsError(msg) {
        if (!itemsErrors) return;
        itemsErrors.innerHTML = msg ? `<p class="field-error">${esc(msg)}</p>` : '';
    }

    function renderLines() {
        tbody.innerHTML = lines
            .map((l, idx) => {
                const lineTotal = (Number(l.unit_price) || 0) * (Number(l.quantity) || 0);
                return `
                <tr data-idx="${idx}">
                    <td>
                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <strong>${esc(l.name)}</strong>
                            <span style="opacity:.75"><code>${esc(l.sku)}</code></span>
                        </div>
                        <input type="hidden" name="items[${idx}][product_id]" value="${esc(l.product_id)}">
                    </td>
                    <td class="num">
                        <input type="number" min="1" step="1" name="items[${idx}][quantity]" value="${esc(l.quantity)}" class="qty-input" style="width:100%;text-align:right;">
                    </td>
                    <td class="num">
                        <input type="number" min="0" step="0.01" value="${esc(l.unit_price)}" class="unit-input" style="width:100%;text-align:right;" disabled>
                    </td>
                    <td class="num"><strong>${esc(formatColones(lineTotal))}</strong></td>
                    <td>
                        <button type="button" class="remove-line" title="Eliminar línea" aria-label="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            })
            .join('');
        updateSummary();
    }

    function addProductToLines(p) {
        const exists = lines.find((l) => l.product_id === p.product_id);
        if (exists) {
            exists.quantity += 1;
        } else {
            lines.push({
                product_id: p.product_id,
                name: p.name,
                sku: p.sku,
                unit_price: Number(p.unit_price) || 0,
                quantity: 1,
            });
        }
        showItemsError('');
        renderLines();
    }

    function openCombobox() {
        if (!comboboxWrapper || !comboboxDropdown || searchInput.disabled) return;
        comboboxWrapper.classList.add('open');
        comboboxDropdown.classList.add('open');
    }

    function closeCombobox() {
        if (!comboboxWrapper || !comboboxDropdown) return;
        comboboxWrapper.classList.remove('open');
        comboboxDropdown.classList.remove('open');
    }

    function renderSearchDropdown(list) {
        if (!comboboxDropdown) return;
        if (!list.length) {
            comboboxDropdown.innerHTML = `<div class="product-combobox-no-result">Sin resultados</div>`;
            openCombobox();
            return;
        }
        comboboxDropdown.innerHTML = list
            .map((p, i) => `
            <div class="product-combobox-option" data-idx="${i}">
                <div class="product-combobox-option-info">
                    <div class="product-combobox-option-name">${esc(p.name)}</div>
                    <div class="product-combobox-option-meta">
                        <code>${esc(p.sku)}</code>
                        <span>${esc(formatColones(p.unit_price))}</span>
                    </div>
                </div>
                <button type="button" class="product-combobox-add-btn" data-idx="${i}" title="Agregar">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>`).join('');
        openCombobox();
        comboboxDropdown.querySelectorAll('.product-combobox-add-btn').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const idx = Number(btn.getAttribute('data-idx'));
                const p = list[idx];
                if (p) addProductToLines(p);
                searchInput.value = '';
                const filtered = allProducts;
                renderSearchDropdown(filtered);
                searchInput.focus();
            });
        });
    }

    searchInput.addEventListener('focus', () => {
        const q = searchInput.value.trim().toLowerCase();
        const filtered = q
            ? allProducts.filter(p => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q))
            : allProducts;
        renderSearchDropdown(filtered);
    });

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        const filtered = q
            ? allProducts.filter(p => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q))
            : allProducts;
        renderSearchDropdown(filtered);
    });

    document.addEventListener('click', (e) => {
        if (!comboboxWrapper) return;
        if (comboboxWrapper.contains(e.target)) return;
        closeCombobox();
    });

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-line');
        if (!btn) return;
        const tr = btn.closest('tr');
        const idx = Number(tr?.getAttribute('data-idx'));
        if (Number.isNaN(idx)) return;
        lines.splice(idx, 1);
        renderLines();
    });

    tbody.addEventListener('input', (e) => {
        const input = e.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (!input.classList.contains('qty-input')) return;
        const tr = input.closest('tr');
        const idx = Number(tr?.getAttribute('data-idx'));
        if (Number.isNaN(idx)) return;
        const v = parseInt(input.value || '0', 10);
        lines[idx].quantity = Number.isNaN(v) ? 0 : v;
        renderLines();
    });

    async function loadSupplierProducts() {
        const supplierId = Number(supplierSelect.value || 0);
        if (!supplierId) {
            allProducts = [];
            searchInput.disabled = true;
            searchInput.placeholder = 'Selecciona un proveedor primero…';
            closeCombobox();
            return;
        }
        searchInput.disabled = false;
        searchInput.placeholder = 'Busca por nombre o SKU (BK-001)…';
        try {
            const data = await fetchProducts('', supplierId);
            allProducts = Array.isArray(data.products) ? data.products : [];
        } catch {
            allProducts = [];
        }
    }

    supplierSelect.addEventListener('change', async () => {
        renderSupplierPreview();
        allProducts = [];
        closeCombobox();
        showItemsError('');
        await loadSupplierProducts();
    });
    renderSupplierPreview();
    loadSupplierProducts();

    form.addEventListener('submit', (e) => {
        const supplierOk = !!supplierSelect.value;
        const dateOk = !!dateInput.value;
        const linesOk = lines.length >= 1 && lines.every((l) => (Number(l.quantity) || 0) >= 1);

        if (!supplierOk || !dateOk || !linesOk) {
            e.preventDefault();
            const missing = [];
            if (!supplierOk) missing.push('Proveedor');
            if (!dateOk) missing.push('fecha estimada');
            if (!linesOk) missing.push('al menos un producto con cantidad');

            if (!linesOk) {
                showItemsError('Agrega al menos un producto y asegúrate de que todas las cantidades sean mayores a 0.');
            }

            if (!supplierOk) {
                supplierSelect.focus();
            } else if (!dateOk) {
                dateInput.focus();
            } else if (!linesOk) {
                searchInput.focus();
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Revisa los campos obligatorios',
                    text:
                        missing.length === 1
                            ? `${missing[0]} es obligatorio.`
                            : `${missing.slice(0, -1).join(', ')} y ${missing[missing.length - 1]} son obligatorios.`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#2e7d32',
                });
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', init);

