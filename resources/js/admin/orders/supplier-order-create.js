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
    const tbody = document.getElementById('items-body');
    const addRandom = document.getElementById('add-random-line');
    const summaryLines = document.getElementById('summary-lines');
    const summaryTotal = document.getElementById('summary-total');
    const itemsErrors = document.getElementById('items-errors');

    if (!form || !supplierSelect || !dateInput || !searchInput || !tbody || !summaryLines || !summaryTotal) return;

    const suppliers = Array.isArray(window.__CF4_SUPPLIERS__) ? window.__CF4_SUPPLIERS__ : [];

    /** @type {Array<{product_id:number,name:string,sku:string,unit_price:number,quantity:number}>} */
    let lines = [];

    /** @type {Array<{product_id:number,name:string,sku:string,unit_price:number}>} */
    let productResults = [];

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

    function renderSearchDropdown(list) {
        let dd = document.getElementById('product-search-dropdown');
        if (!dd) {
            dd = document.createElement('div');
            dd.id = 'product-search-dropdown';
            dd.style.position = 'absolute';
            dd.style.zIndex = '1200';
            dd.style.left = '0';
            dd.style.right = '0';
            dd.style.top = 'calc(100% + 6px)';
            dd.style.background = '#fff';
            dd.style.border = '1px solid rgba(0,0,0,0.12)';
            dd.style.borderRadius = '12px';
            dd.style.boxShadow = '0 10px 30px rgba(0,0,0,0.08)';
            dd.style.maxHeight = '260px';
            dd.style.overflowY = 'auto';
            dd.style.padding = '6px';
            searchInput.parentElement.style.position = 'relative';
            searchInput.parentElement.appendChild(dd);
        }
        if (!list.length) {
            dd.innerHTML = `<div style="padding:10px;color:#5f6368;">Sin resultados</div>`;
            dd.hidden = false;
            return;
        }
        dd.innerHTML = list
            .map(
                (p, i) => `
            <button type="button" class="dd-item" data-idx="${i}"
              style="width:100%;text-align:left;border:none;background:transparent;padding:10px;border-radius:10px;cursor:pointer;">
              <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                <div>
                  <div style="font-weight:700;">${esc(p.name)}</div>
                  <div style="opacity:.75;font-size:.85rem;"><code>${esc(p.sku)}</code></div>
                </div>
                <div style="white-space:nowrap;font-weight:700;color:#0f172a;">${esc(formatColones(p.unit_price))}</div>
              </div>
            </button>`
            )
            .join('');
        dd.hidden = false;

        dd.querySelectorAll('.dd-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.getAttribute('data-idx'));
                const p = productResults[idx];
                if (p) addProductToLines(p);
                dd.hidden = true;
                searchInput.value = '';
                searchInput.focus();
            });
        });
    }

    let timer = null;
    searchInput.addEventListener('input', () => {
        window.clearTimeout(timer);
        const q = searchInput.value.trim();
        if (q.length < 2) return;

        const supplierId = Number(supplierSelect.value || 0);
        if (!supplierId) {
            showItemsError('Selecciona un proveedor para poder buscar productos.');
            return;
        }

        timer = window.setTimeout(async () => {
            try {
                const data = await fetchProducts(q, supplierId);
                productResults = Array.isArray(data.products) ? data.products : [];
                renderSearchDropdown(productResults);
            } catch (err) {
                const msg = err instanceof Error ? err.message : 'No se pudo buscar productos.';
                // eslint-disable-next-line no-console
                console.warn('Product search failed', err);
                showItemsError(msg);
                productResults = [];
                renderSearchDropdown([]);
            }
        }, 200);
    });

    document.addEventListener('click', (e) => {
        const dd = document.getElementById('product-search-dropdown');
        if (!dd) return;
        if (dd.contains(e.target) || searchInput.contains(e.target)) return;
        dd.hidden = true;
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

    if (addRandom) {
        addRandom.addEventListener('click', () => {
            // Adds an empty line that forces user to search and pick a product
            showItemsError('Busca un producto y selecciónalo para agregarlo.');
            searchInput.focus();
        });
    }

    function updateSearchAvailability() {
        const supplierId = Number(supplierSelect.value || 0);
        const disabled = !supplierId;
        searchInput.disabled = disabled;
        searchInput.placeholder = disabled
            ? 'Selecciona un proveedor para buscar productos…'
            : 'Busca por nombre o SKU (BK-001)…';
        if (disabled) {
            const dd = document.getElementById('product-search-dropdown');
            if (dd) dd.hidden = true;
        }
    }

    supplierSelect.addEventListener('change', () => {
        renderSupplierPreview();
        updateSearchAvailability();
        showItemsError('');
    });
    renderSupplierPreview();
    updateSearchAvailability();

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

