import {
    qs,
    qsa,
    getCSRFToken,
    jsonHeaders,
    escapeHtml,
    escapeHtmlAttr,
    safeParseJsonResponse,
    readJsonOrThrow,
    smartFetch,
    jsonValidationMessage,
} from './inventory-shared.js';
import { fireSwal } from '../shared/swal.js';

function collectClassificationValueIds(container) {
    const ids = [];
    if (!container) return ids;
    container.querySelectorAll('.js-cf-value-id').forEach((inp) => {
        const v = inp.value?.trim();
        if (v && !inp.disabled) {
            const n = Number(v);
            if (!Number.isNaN(n)) ids.push(n);
        }
    });
    return ids;
}

function classificationSelectionMapFromPreset(attrs, presetIds) {
    const set = new Set((presetIds || []).map((x) => Number(x)));
    const map = {};
    attrs.forEach((attr) => {
        (attr.values || []).forEach((v) => {
            if (set.has(Number(v.id))) {
                map[attr.id] = Number(v.id);
            }
        });
    });
    return map;
}

/** Cierra los combobox de otras tarjetas del mismo editor (evita solapamiento visual). */
function closeSiblingClassificationDropdowns(activeCard) {
    const editor = activeCard.closest('.classification-editor');
    if (!editor) return;
    editor.querySelectorAll('.classification-card').forEach((c) => {
        if (c === activeCard) return;
        c.dispatchEvent(new CustomEvent('cf-force-close', { bubbles: false }));
    });
}

function setupClassificationDimensionCard(card, dimension, initialValueId) {
    const dimId = dimension.id;
    let values = (dimension.values || []).map((v) => ({
        id: Number(v.id),
        value: String(v.value),
    }));
    const hidden = card.querySelector('.js-cf-value-id');
    const searchInput = card.querySelector('.js-cf-search');
    const listEl = card.querySelector('.js-cf-list');
    const chipWrap = card.querySelector('.js-cf-chip');
    const errEl = card.querySelector('.js-cf-err');
    let selectedId =
        initialValueId && values.some((v) => v.id === Number(initialValueId))
            ? Number(initialValueId)
            : null;
    let open = false;
    const listPortal = createDropdownPortal(searchInput, listEl);

    card.addEventListener('cf-force-close', () => {
        open = false;
        if (listEl) listEl.hidden = true;
        card.classList.remove('is-dropdown-open');
        listPortal.unmount();
    });

    function setError(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.hidden = !msg;
    }

    function syncHidden() {
        if (!hidden) return;
        if (selectedId) {
            hidden.value = String(selectedId);
            hidden.removeAttribute('disabled');
        } else {
            hidden.value = '';
            hidden.setAttribute('disabled', 'disabled');
        }
    }

    function renderChip() {
        if (!chipWrap) return;
        chipWrap.innerHTML = '';
        const v = values.find((x) => x.id === selectedId);
        if (!v) {
            chipWrap.hidden = true;
            return;
        }
        chipWrap.hidden = false;
        const chip = document.createElement('span');
        chip.className = 'cf-chip';
        chip.textContent = v.value;
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'cf-chip__clear';
        clearBtn.setAttribute('aria-label', 'Quitar valor');
        clearBtn.textContent = '×';
        clearBtn.addEventListener('mousedown', (e) => e.preventDefault());
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            selectedId = null;
            syncHidden();
            renderChip();
            if (searchInput) searchInput.value = '';
            open = false;
            if (listEl) listEl.hidden = true;
            card.classList.remove('is-dropdown-open');
        });
        chipWrap.appendChild(chip);
        chipWrap.appendChild(clearBtn);
    }

    function renderList() {
        if (!listEl || !searchInput) return;
        const q = searchInput.value.trim().toLowerCase();
        const filtered = !q ? values : values.filter((v) => v.value.toLowerCase().includes(q));
        listEl.innerHTML = '';
        filtered.forEach((v) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cf-list__item';
            btn.textContent = v.value;
            btn.addEventListener('mousedown', (e) => e.preventDefault());
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                selectedId = v.id;
                syncHidden();
                renderChip();
                searchInput.value = v.value;
                open = false;
                listEl.hidden = true;
                card.classList.remove('is-dropdown-open');
                setError('');
            });
            listEl.appendChild(btn);
        });
        const t = searchInput.value.trim();
        if (t) {
            const exact = values.some((v) => v.value.toLowerCase() === t.toLowerCase());
            if (!exact) {
                const createBtn = document.createElement('button');
                createBtn.type = 'button';
                createBtn.className = 'cf-list__create';
                createBtn.textContent = `+ Crear valor «${t}»`;
                createBtn.addEventListener('mousedown', (e) => e.preventDefault());
                createBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    createValueApi(t);
                });
                listEl.appendChild(createBtn);
            }
        }
        const hasRows = listEl.childNodes.length > 0;
        const visible = open && hasRows;
        listEl.hidden = !visible;
        card.classList.toggle('is-dropdown-open', Boolean(visible));
        if (visible) {
            listPortal.mount();
        } else {
            listPortal.unmount();
        }
    }

    async function createValueApi(raw) {
        setError('');
        try {
            const res = await smartFetch(CF_API.storeValue(dimId), {
                method: 'POST',
                headers: {
                    ...jsonHeaders(),
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                body: JSON.stringify({ value: raw }),
            });
            const data = await readJsonOrThrow(res, 'No se pudo crear el valor.');
            const nv = data.value;
            values.push({ id: Number(nv.id), value: String(nv.value) });
            selectedId = Number(nv.id);
            syncHidden();
            renderChip();
            searchInput.value = nv.value;
            open = false;
            listEl.hidden = true;
            card.classList.remove('is-dropdown-open');
            showSubtleNotification('Valor guardado en el catálogo', 'success');
        } catch (err) {
            const msg = err?.data ? jsonValidationMessage(err.data) : err.message;
            setError(msg || 'No se pudo crear el valor.');
        }
    }

    searchInput.addEventListener('focus', () => {
        closeSiblingClassificationDropdowns(card);
        open = true;
        renderList();
    });
    searchInput.addEventListener('input', () => {
        closeSiblingClassificationDropdowns(card);
        open = true;
        renderList();
    });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const t = searchInput.value.trim();
            if (!t) return;
            const found = values.find((v) => v.value.toLowerCase() === t.toLowerCase());
            if (found) {
                selectedId = found.id;
                syncHidden();
                renderChip();
                open = false;
                listEl.hidden = true;
                card.classList.remove('is-dropdown-open');
                setError('');
                return;
            }
            createValueApi(t);
        }
        if (e.key === 'Escape') {
            open = false;
            listEl.hidden = true;
            card.classList.remove('is-dropdown-open');
        }
    });

    if (selectedId) {
        const v = values.find((x) => x.id === selectedId);
        if (v) searchInput.value = v.value;
    }
    syncHidden();
    renderChip();
}

function buildDimensionCard(attr, initialValueId) {
    const card = document.createElement('div');
    card.className = 'classification-card';
    card.dataset.cfDimensionId = String(attr.id);

    const head = document.createElement('div');
    head.className = 'classification-card__head';
    const title = document.createElement('span');
    title.className = 'classification-card__title';
    title.textContent = attr.label || 'Atributo';
    head.appendChild(title);
    if (attr.slug) {
        const slug = document.createElement('span');
        slug.className = 'classification-card__slug';
        slug.textContent = attr.slug;
        head.appendChild(slug);
    }
    card.appendChild(head);

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.className = 'js-cf-value-id';
    hidden.name = 'classification_value_ids[]';
    hidden.setAttribute('disabled', 'disabled');
    card.appendChild(hidden);

    const chipWrap = document.createElement('div');
    chipWrap.className = 'cf-chip-wrap js-cf-chip';
    chipWrap.hidden = true;
    card.appendChild(chipWrap);

    const combo = document.createElement('div');
    combo.className = 'cf-combobox';
    const search = document.createElement('input');
    search.type = 'text';
    search.className = 'cf-combobox__input js-cf-search';
    search.setAttribute('autocomplete', 'off');
    search.setAttribute('placeholder', 'Buscar, elegir o escribir y pulsá Enter…');
    search.setAttribute('aria-label', `Valor de ${String(attr.label || 'atributo')}`);
    const list = document.createElement('div');
    list.className = 'cf-combobox__list js-cf-list';
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    combo.appendChild(search);
    combo.appendChild(list);
    card.appendChild(combo);

    const err = document.createElement('p');
    err.className = 'cf-field-error js-cf-err';
    err.hidden = true;
    card.appendChild(err);

    setupClassificationDimensionCard(card, attr, initialValueId);
    return card;
}

async function refreshClassificationFields(containerSelector, categoryId, preselectedIds) {
    const container = qs(containerSelector);
    if (!container) return;

    if (container._cfOutsideAbort) {
        container._cfOutsideAbort.abort();
    }

    const section = container.closest('[id$="classification-section"]');
    const hint = section?.querySelector('.classification-section-hint');

    container.classList.add('classification-fields-root');
    container.innerHTML = '';

    if (!categoryId) {
        if (hint) {
            hint.textContent =
                'Elegí categoría y, si aplica, subcategoría. Los atributos se configuran por tipo de producto (subcategoría).';
            hint.hidden = false;
        }
        return;
    }

    const loading = document.createElement('p');
    loading.className = 'classification-loading';
    loading.textContent = 'Cargando atributos…';
    container.appendChild(loading);

    let data;
    try {
        const r = await fetch(CF_API.options(categoryId), {
            credentials: 'same-origin',
            headers: jsonHeaders(),
        });
        if (!r.ok) throw new Error('options');
        data = await r.json();
    } catch {
        container.innerHTML = '';
        const p = document.createElement('p');
        p.className = 'form-text';
        p.style.color = '#b91c1c';
        p.textContent = 'No se pudieron cargar atributos. Probá de nuevo.';
        container.appendChild(p);
        return;
    }

    container.innerHTML = '';
    const attrs = data.attributes || data.dimensions || [];
    const preset = Array.isArray(preselectedIds) ? preselectedIds.map((x) => Number(x)) : [];
    const selMap = classificationSelectionMapFromPreset(attrs, preset);

    const tree = window.inventoryCategoryTree || {};
    const isParentOnlyWithSubcategories = Array.isArray(tree[String(categoryId)]);

    if (isParentOnlyWithSubcategories) {
        const warn = document.createElement('div');
        warn.className = 'classification-parent-only-msg';
        warn.setAttribute('role', 'note');
        warn.textContent =
            'Para usar atributos (color, talla…), elegí una subcategoría concreta. Si guardás solo la categoría padre, la clasificación por atributos no aplica.';
        container.appendChild(warn);
        if (hint) {
            hint.textContent =
                'Los atributos están disponibles cuando el producto queda clasificado en una subcategoría (tipo de producto).';
            hint.hidden = false;
        }
        return;
    }

    if (hint) {
        hint.textContent =
            'Un valor por atributo en este producto. Podés crear atributos y valores aquí; quedan guardados en el catálogo de esta subcategoría.';
        hint.hidden = false;
    }

    const editor = document.createElement('div');
    editor.className = 'classification-editor';

    const toolbar = document.createElement('div');
    toolbar.className = 'classification-editor__toolbar';
    const dimLabel = document.createElement('input');
    dimLabel.type = 'text';
    dimLabel.className = 'cf-toolbar-input';
    dimLabel.setAttribute('placeholder', 'Nuevo atributo (ej. Material, Color)');
    dimLabel.setAttribute('aria-label', 'Nombre del nuevo atributo');
    const dimBtn = document.createElement('button');
    dimBtn.type = 'button';
    dimBtn.className = 'btn btn-sm btn-primary cf-toolbar-btn';
    dimBtn.innerHTML = '<i class="fas fa-plus"></i> Crear atributo';
    const toolbarFeedback = document.createElement('p');
    toolbarFeedback.className = 'cf-toolbar-feedback';
    toolbarFeedback.hidden = true;
    toolbarFeedback.setAttribute('aria-hidden', 'true');
    toolbarFeedback.id =
        String(containerSelector).includes('edit') ? 'cf-edit-attr-toolbar-feedback' : 'cf-new-attr-toolbar-feedback';
    dimLabel.setAttribute('aria-describedby', toolbarFeedback.id);

    function clearToolbarAttrFeedback() {
        toolbarFeedback.textContent = '';
        toolbarFeedback.hidden = true;
        toolbarFeedback.setAttribute('aria-hidden', 'true');
        toolbarFeedback.removeAttribute('role');
        toolbarFeedback.classList.remove('cf-toolbar-feedback--error', 'cf-toolbar-feedback--success');
        dimLabel.classList.remove('is-invalid');
        dimLabel.removeAttribute('aria-invalid');
    }

    function setToolbarAttrFeedback(message, kind = 'error') {
        toolbarFeedback.textContent = message;
        toolbarFeedback.hidden = false;
        toolbarFeedback.removeAttribute('aria-hidden');
        toolbarFeedback.classList.remove('cf-toolbar-feedback--error', 'cf-toolbar-feedback--success');
        toolbarFeedback.classList.add(
            kind === 'success' ? 'cf-toolbar-feedback--success' : 'cf-toolbar-feedback--error'
        );
        toolbarFeedback.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        dimLabel.classList.toggle('is-invalid', kind === 'error');
        dimLabel.setAttribute('aria-invalid', kind === 'error' ? 'true' : 'false');
    }

    dimLabel.addEventListener('input', () => {
        if (toolbarFeedback.textContent) clearToolbarAttrFeedback();
    });

    toolbar.appendChild(dimLabel);
    toolbar.appendChild(dimBtn);
    toolbar.appendChild(toolbarFeedback);
    editor.appendChild(toolbar);

    dimBtn.addEventListener('click', async () => {
        const label = dimLabel.value.trim();
        if (!label) {
            setToolbarAttrFeedback('Escribí un nombre para el atributo.', 'error');
            dimLabel.focus();
            return;
        }
        clearToolbarAttrFeedback();
        dimBtn.disabled = true;
        try {
            const res = await smartFetch(CF_API.storeDimension(categoryId), {
                method: 'POST',
                headers: {
                    ...jsonHeaders(),
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                body: JSON.stringify({ label }),
            });
            await readJsonOrThrow(res, 'No se pudo crear el atributo.');
            dimLabel.value = '';
            showSubtleNotification('Atributo creado', 'success');
            const preserved = collectClassificationValueIds(container);
            await refreshClassificationFields(containerSelector, categoryId, preserved);
        } catch (e) {
            const msg = e?.data ? jsonValidationMessage(e.data) : e.message;
            setToolbarAttrFeedback(msg || 'No se pudo crear el atributo.', 'error');
        } finally {
            dimBtn.disabled = false;
        }
    });

    if (!attrs.length) {
        const empty = document.createElement('div');
        empty.className = 'classification-empty';
        const t1 = document.createElement('p');
        t1.className = 'classification-empty__title';
        t1.textContent = 'Aún no hay atributos para este tipo';
        const t2 = document.createElement('p');
        t2.className = 'classification-empty__text';
        t2.textContent =
            'Creá el primero con «Crear atributo» (por ejemplo Color o Talla). Luego agregás valores en cada tarjeta.';
        empty.appendChild(t1);
        empty.appendChild(t2);
        editor.appendChild(empty);
        container.appendChild(editor);

        container._cfOutsideAbort = new AbortController();
        const { signal } = container._cfOutsideAbort;
        document.addEventListener(
            'mousedown',
            (ev) => {
                if (!container.contains(ev.target)) {
                    container.querySelectorAll('.classification-card').forEach((c) => {
                        c.dispatchEvent(new CustomEvent('cf-force-close', { bubbles: false }));
                    });
                }
            },
            { signal }
        );
        return;
    }

    attrs.forEach((attr) => {
        const initial = selMap[attr.id] ?? null;
        editor.appendChild(buildDimensionCard(attr, initial));
    });

    container.appendChild(editor);

    container._cfOutsideAbort = new AbortController();
    const { signal } = container._cfOutsideAbort;
    document.addEventListener(
        'mousedown',
        (ev) => {
            if (!container.contains(ev.target)) {
                container.querySelectorAll('.classification-card').forEach((c) => {
                    c.dispatchEvent(new CustomEvent('cf-force-close', { bubbles: false }));
                });
            }
        },
        { signal }
    );
}

export const CF_API = {
    options: (categoryId) => `/classifications/catalog/${encodeURIComponent(categoryId)}/options`,
    storeDimension: (categoryId) =>
        `/classifications/catalog/${encodeURIComponent(categoryId)}/dimensions`,
    storeValue: (dimensionId) =>
        `/classifications/dimensions/${encodeURIComponent(dimensionId)}/values`,
};

export {
    refreshClassificationFields,
    collectClassificationValueIds,
    setupClassificationDimensionCard,
    buildDimensionCard,
    classificationSelectionMapFromPreset,
    closeSiblingClassificationDropdowns,
};
