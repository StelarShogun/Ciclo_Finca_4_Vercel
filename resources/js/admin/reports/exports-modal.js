function qs(root, sel) {
  const el = root.querySelector(sel);
  if (!el) throw new Error(`Missing element: ${sel}`);
  return el;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function createField(field, initialValue) {
  const wrapper = document.createElement("div");
  wrapper.className = "cf4-export-modal__field";

  const label = document.createElement("label");
  label.className = "cf4-export-modal__label";
  label.htmlFor = `cf4-export-field-${field.name}`;
  label.textContent = field.label ?? field.name;

  let input;
  if (field.type === "select") {
    input = document.createElement("select");
    input.className = "cf4-export-modal__control";
    input.name = field.name;
    input.id = `cf4-export-field-${field.name}`;

    const options = Array.isArray(field.options) ? field.options : [];
    for (const opt of options) {
      const o = document.createElement("option");
      o.value = opt.value;
      o.textContent = opt.label;
      input.appendChild(o);
    }
  } else {
    input = document.createElement("input");
    input.className = "cf4-export-modal__control";
    input.type = field.type ?? "text";
    input.name = field.name;
    input.id = `cf4-export-field-${field.name}`;
    if (field.placeholder) input.placeholder = field.placeholder;
    if (field.readonly) {
      input.readOnly = true;
      input.tabIndex = -1;
      input.style.background = "var(--cf4-input-bg-disabled, #f4f4f4)";
      input.style.color = "var(--cf4-text-muted, #888)";
      input.style.cursor = "default";
    }
  }

  if (field.help) {
    const help = document.createElement("div");
    help.className = "cf4-export-modal__help";
    help.innerHTML = escapeHtml(field.help);
    wrapper.appendChild(help);
  }

  if (initialValue !== undefined && initialValue !== null) {
    input.value = String(initialValue);
  } else if (field.default !== undefined) {
    input.value = String(field.default);
  }

  wrapper.appendChild(label);
  wrapper.appendChild(input);
  return wrapper;
}

function buildUrl(def, format, scopeValue, values) {
  const params = new URLSearchParams();

  if (def.formatMode === "query") {
    params.set("format", format);
  }

  if (scopeValue === "all") {
    params.set("scope", "all");
  } else {
    for (const field of def.filters ?? []) {
      const v = values[field.name];
      if (v !== undefined && v !== null && String(v).trim() !== "") {
        params.set(field.name, String(v));
      }
    }
  }

  // allow passing through any static params
  if (def.staticParams) {
    for (const [k, v] of Object.entries(def.staticParams)) {
      if (v !== undefined && v !== null && String(v) !== "") params.set(k, String(v));
    }
  }

  const base = def.baseUrls?.[format];
  if (!base) throw new Error(`Missing base URL for ${def.id} ${format}`);

  const url = new URL(base, window.location.origin);
  // merge existing base query (if any)
  for (const [k, v] of new URLSearchParams(url.search)) {
    if (!params.has(k)) params.set(k, v);
  }
  url.search = params.toString();
  return url.toString();
}

function initExportsModal() {
  const dialog = document.getElementById("cf4-export-modal");
  if (!dialog) return;

  const configEl = document.getElementById("cf4-export-config");
  if (!configEl) return;

  /** @type {{ exports: Record<string, any> }} */
  const config = JSON.parse(configEl.textContent || "{}");
  const exportsMap = config.exports ?? {};

  const titleEl = qs(dialog, "#cf4-export-modal-title");
  const subtitleEl = qs(dialog, "#cf4-export-modal-subtitle");
  const filtersRoot = qs(dialog, "[data-export-modal-filters]");
  const closeBtns = dialog.querySelectorAll("[data-export-modal-close]");
  const submitBtn = qs(dialog, "[data-export-modal-submit]");

  let active = { exportId: null, format: null };

  const open = (exportId, format) => {
    const def = exportsMap[exportId];
    if (!def) throw new Error(`Unknown export: ${exportId}`);

    active = { exportId, format };

    titleEl.textContent = def.title ?? "Exportar";
    subtitleEl.textContent = def.subtitle ?? "";

    // reset scope
    const allRadio = dialog.querySelector('input[name="scope"][value="all"]');
    if (allRadio) allRadio.checked = true;

    // rebuild filters UI
    filtersRoot.innerHTML = "";
    const initial = def.initialValues ?? {};
    for (const field of def.filters ?? []) {
      filtersRoot.appendChild(createField(field, initial[field.name]));
    }

    // ── Cascading dropdowns ──────────────────────────────────────────────────
    for (const field of def.filters ?? []) {
      if (!field.cascades || !field.cascadeOptions) continue;
      const parentSel = filtersRoot.querySelector(`[name="${CSS.escape(field.name)}"]`);
      const childSel  = filtersRoot.querySelector(`[name="${CSS.escape(field.cascades)}"]`);
      if (!parentSel || !childSel) continue;

      const updateChild = (parentValue) => {
        const opts = field.cascadeOptions[parentValue] ?? [{ value: "", label: "Todas" }];
        childSel.innerHTML = "";
        for (const opt of opts) {
          const o = document.createElement("option");
          o.value = opt.value;
          o.textContent = opt.label;
          childSel.appendChild(o);
        }
      };

      // seed with current parent value
      updateChild(parentSel.value);

      parentSel.addEventListener("change", (e) => updateChild(e.target.value));
    }

    // ── Autofill fields ──────────────────────────────────────────────────────
    for (const field of def.filters ?? []) {
      if (!field.autofills || !field.autofillData) continue;
      const sourceSel = filtersRoot.querySelector(`[name="${CSS.escape(field.name)}"]`);
      if (!sourceSel) continue;

      const applyAutofill = (selectedValue) => {
        const data = field.autofillData[selectedValue] ?? {};
        for (const targetName of field.autofills) {
          const targetEl = filtersRoot.querySelector(`[name="${CSS.escape(targetName)}"]`);
          if (targetEl) {
            targetEl.value = data[targetName] ?? "";
          }
        }
      };

      // seed on open
      applyAutofill(sourceSel.value);

      sourceSel.addEventListener("change", (e) => applyAutofill(e.target.value));
    }

    // show/hide filters based on scope
    filtersRoot.style.display = "none";

    dialog.showModal();
  };

  const close = () => {
    if (dialog.open) dialog.close();
  };

  for (const btn of closeBtns) {
    btn.addEventListener("click", close);
  }
  dialog.addEventListener("click", (e) => {
    // close when clicking backdrop
    if (e.target === dialog) close();
  });

  dialog.addEventListener("change", (e) => {
    const t = e.target;
    if (!(t instanceof HTMLInputElement)) return;
    if (t.name !== "scope") return;
    filtersRoot.style.display = t.value === "filtered" ? "block" : "none";
  });

  submitBtn.addEventListener("click", () => {
    const def = exportsMap[active.exportId];
    if (!def) return;

    const scope = dialog.querySelector('input[name="scope"]:checked')?.value ?? "all";
    const values = {};
    for (const field of def.filters ?? []) {
      const el = dialog.querySelector(`[name="${CSS.escape(field.name)}"]`);
      if (el && "value" in el) values[field.name] = el.value;
    }

    const url = buildUrl(def, active.format, scope, values);
    window.open(url, "_blank", "noopener,noreferrer");
    close();
  });

  // attach listeners to all export buttons
  document.addEventListener("click", (e) => {
    const target = e.target instanceof Element ? e.target.closest("[data-export-id][data-export-format]") : null;
    if (!target) return;
    e.preventDefault();
    const exportId = target.getAttribute("data-export-id");
    const format = target.getAttribute("data-export-format");
    if (!exportId || !format) return;
    open(exportId, format);
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initExportsModal);
} else {
  initExportsModal();
}

