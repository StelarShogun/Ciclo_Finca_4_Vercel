import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';

import { Modal } from '@/shared/components/ui/Modal';
import { CollapsibleSection } from '@/shared/components/ui/CollapsibleSection';
import { FileUpload } from '@/shared/components/ui/FileUpload';
import { useToast } from '@/shared/hooks/useToast';
import { compressImageFile, compressFileList } from '@/shared/lib/imageCompression';

import type { BrandOption, ClassificationAttribute, CategoryOption, SubByParent, SupplierOption } from '../types';
import { VariantsManager } from './VariantsManager';

type ProductFormModalProps = {
  open: boolean;
  editingId: number | null;
  csrfToken: string;
  categories: CategoryOption[];
  subcategoriesByParent: SubByParent;
  brands: BrandOption[];
  suppliers: SupplierOption[];
  onClose: () => void;
  onSaved: () => void;
};

type FormState = {
  name: string;
  description: string;
  parent_category_id: string;
  category_id: string;
  supplier_id: string;
  brand_id: string;
  purchase_price: string;
  sale_price: string;
  stock_current: string;
  stock_minimum: string;
  status: string;
  is_featured: boolean;
};

const EMPTY: FormState = {
  name: '',
  description: '',
  parent_category_id: '',
  category_id: '',
  supplier_id: '',
  brand_id: '',
  purchase_price: '',
  sale_price: '',
  stock_current: '',
  stock_minimum: '',
  status: 'active',
  is_featured: false,
};

const STATUSES = [
  { value: 'active', label: 'Activo' },
  { value: 'inactive', label: 'Inactivo' },
  { value: 'out_of_stock', label: 'Agotado' },
  { value: 'discontinued', label: 'Descontinuado' },
];

export function ProductFormModal({
  brands,
  categories,
  csrfToken,
  editingId,
  onClose,
  onSaved,
  open,
  subcategoriesByParent,
  suppliers,
}: ProductFormModalProps) {
  const { showToast } = useToast();
  const [form, setForm] = useState<FormState>(EMPTY);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [mainImage, setMainImage] = useState<File | null>(null);
  const [gallery, setGallery] = useState<FileList | null>(null);
  const [attributes, setAttributes] = useState<ClassificationAttribute[]>([]);
  const [selectedValues, setSelectedValues] = useState<Record<number, string>>({});
  const [classificationPreset, setClassificationPreset] = useState<number[]>([]);
  const [currentImage, setCurrentImage] = useState<string | null>(null);
  const isEdit = editingId !== null;

  const subcategories = form.parent_category_id ? subcategoriesByParent[form.parent_category_id] ?? [] : [];

  // Cargar datos al abrir en modo edición.
  useEffect(() => {
    if (!open) {
      return;
    }
    if (!isEdit) {
      setForm(EMPTY);
      setErrors({});
      setSelectedValues({});
      setMainImage(null);
      setGallery(null);
      setCurrentImage(null);
      setAttributes([]);
      return;
    }
    let active = true;
    fetch(`/products/${editingId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.json())
      .then((payload) => {
        if (!active || !payload?.success) return;
        const p = payload.data;
        const parentId = p.category?.parent?.category_id ?? p.category?.parent_category_id ?? '';
        setForm({
          name: p.name ?? '',
          description: p.description ?? '',
          parent_category_id: String(parentId || ''),
          category_id: String(p.category_id ?? ''),
          supplier_id: String(p.supplier_id ?? ''),
          brand_id: String(p.brand_id ?? ''),
          purchase_price: String(p.purchase_price ?? ''),
          sale_price: String(p.sale_price ?? ''),
          stock_current: String(p.stock_current ?? ''),
          stock_minimum: String(p.stock_minimum ?? ''),
          status: p.status ?? 'active',
          is_featured: Boolean(p.is_featured),
        });
        setCurrentImage(p.uses_placeholder_image ? null : p.media_main ?? null);
        // Los ids seleccionados se mapean a su atributo cuando cargan las opciones.
        setClassificationPreset((p.classification_value_ids ?? []).map((id: number) => Number(id)));
        setSelectedValues({});
        setErrors({});
        setMainImage(null);
        setGallery(null);
      });
    return () => {
      active = false;
    };
  }, [open, editingId, isEdit]);

  // Cargar atributos de clasificación cuando cambia la subcategoría (category_id concreto).
  useEffect(() => {
    const categoryForAttributes = form.category_id || form.parent_category_id;
    if (!open || !categoryForAttributes) {
      setAttributes([]);
      return;
    }
    let active = true;
    fetch(`/classifications/catalog/${categoryForAttributes}/options`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then((payload) => {
        if (!active) return;
        const attrs: ClassificationAttribute[] = payload?.attributes ?? [];
        setAttributes(attrs);
        // Precargar el valor seleccionado por atributo desde el preset (edición).
        if (classificationPreset.length > 0) {
          const presetSet = new Set(classificationPreset);
          const next: Record<number, string> = {};
          attrs.forEach((attr) => {
            const match = attr.values.find((value) => presetSet.has(value.id));
            if (match) {
              next[attr.id] = String(match.id);
            }
          });
          setSelectedValues(next);
        }
      })
      .catch(() => active && setAttributes([]));
    return () => {
      active = false;
    };
  }, [open, form.category_id, form.parent_category_id, classificationPreset]);

  function set<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  async function submit(event: FormEvent) {
    event.preventDefault();
    setErrors({});
    setSaving(true);

    const body = new FormData();
    body.append('name', form.name);
    body.append('description', form.description);
    body.append('parent_category_id', form.parent_category_id);
    // Sin subcategoría, la categoría es la categoría padre.
    body.append('category_id', form.category_id || form.parent_category_id);
    body.append('supplier_id', form.supplier_id);
    body.append('brand_id', form.brand_id);
    body.append('purchase_price', form.purchase_price);
    body.append('sale_price', form.sale_price);
    body.append('stock_current', form.stock_current);
    body.append('stock_minimum', form.stock_minimum);
    body.append('status', form.status);
    body.append('is_featured', form.is_featured ? '1' : '0');

    Object.values(selectedValues)
      .filter((v) => v !== '')
      .forEach((valueId) => body.append('classification_value_ids[]', valueId));

    if (mainImage) {
      body.append('image', await compressImageFile(mainImage));
    }
    if (gallery && gallery.length > 0) {
      const compressed = await compressFileList(Array.from(gallery));
      compressed.forEach((file) => body.append('images[]', file));
    }

    const url = isEdit ? `/products/${editingId}` : '/products';
    if (isEdit) {
      body.append('_method', 'PUT');
    }

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        body,
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        if (data.errors) {
          const flat: Record<string, string> = {};
          Object.entries(data.errors as Record<string, string[]>).forEach(([k, v]) => {
            flat[k] = v[0];
          });
          setErrors(flat);
          showToast({ variant: 'error', title: 'Revisá el formulario', message: 'Hay campos con errores.' });
        } else {
          showToast({ variant: 'error', title: 'Error', message: data.message ?? 'No se pudo guardar el producto.' });
        }
        return;
      }
      showToast({ variant: 'success', title: isEdit ? 'Producto actualizado' : 'Producto creado', message: data.message });
      onSaved();
    } catch {
      showToast({ variant: 'error', title: 'Error', message: 'Error de conexión al guardar el producto.' });
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal
      isOpen={open}
      onClose={onClose}
      title={
        <>
          <i className={`fas ${isEdit ? 'fa-pen-to-square' : 'fa-box'}`} aria-hidden="true" />
          {isEdit ? 'Editar producto' : 'Nuevo producto'}
        </>
      }
      className="cf4-modal cf4-modal--wide"
      footer={
        <>
          <button type="button" className="btn btn-secondary" onClick={onClose}>
            Cancelar
          </button>
          <button type="submit" form="form-product" className="btn btn-primary" disabled={saving}>
            {saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Crear producto'}
          </button>
        </>
      }
    >
      <form id="form-product" onSubmit={submit} className="product-form">
        <CollapsibleSection title="Datos básicos" icon="fa-circle-info">
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-name">Nombre *</label>
              <input id="p-name" type="text" maxLength={150} value={form.name} onChange={(e) => set('name', e.target.value)} required />
              {errors.name ? <div className="field-error">{errors.name}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="p-desc">Descripción</label>
              <textarea id="p-desc" rows={2} maxLength={1000} value={form.description} onChange={(e) => set('description', e.target.value)} />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-parent">Categoría *</label>
              <select
                id="p-parent"
                value={form.parent_category_id}
                onChange={(e) => {
                  set('parent_category_id', e.target.value);
                  set('category_id', '');
                }}
                required
              >
                <option value="">Seleccioná…</option>
                {categories.map((c) => (
                  <option key={c.category_id} value={String(c.category_id)}>{c.name}</option>
                ))}
              </select>
              {errors.parent_category_id ? <div className="field-error">{errors.parent_category_id}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="p-sub">Subcategoría (recomendado)</label>
              <select
                id="p-sub"
                value={form.category_id}
                onChange={(e) => set('category_id', e.target.value)}
                disabled={!form.parent_category_id}
              >
                <option value="">Sin subcategoría</option>
                {subcategories.map((s) => (
                  <option key={s.category_id} value={String(s.category_id)}>{s.name}</option>
                ))}
              </select>
              {errors.category_id ? <div className="field-error">{errors.category_id}</div> : null}
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-supplier">Proveedor *</label>
              <select id="p-supplier" value={form.supplier_id} onChange={(e) => set('supplier_id', e.target.value)} required>
                <option value="">Seleccioná…</option>
                {suppliers.map((s) => (
                  <option key={s.supplier_id} value={String(s.supplier_id)}>{s.name}</option>
                ))}
              </select>
              {errors.supplier_id ? <div className="field-error">{errors.supplier_id}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="p-brand">Marca *</label>
              <select id="p-brand" value={form.brand_id} onChange={(e) => set('brand_id', e.target.value)} required>
                <option value="">Seleccioná…</option>
                {brands.map((b) => (
                  <option key={b.id} value={String(b.id)}>{b.name}</option>
                ))}
              </select>
              {errors.brand_id ? <div className="field-error">{errors.brand_id}</div> : null}
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="p-status">Estado *</label>
            <select id="p-status" value={form.status} onChange={(e) => set('status', e.target.value)} required>
              {STATUSES.map((s) => (
                <option key={s.value} value={s.value}>{s.label}</option>
              ))}
            </select>
          </div>

          <label className="cf4-switch">
            <input type="checkbox" checked={form.is_featured} onChange={(e) => set('is_featured', e.target.checked)} />
            <span className="cf4-switch__track" aria-hidden="true"><span className="cf4-switch__thumb" /></span>
            <span className="cf4-switch__text">
              <span className="cf4-switch__title">Destacado en tienda</span>
              <span className="cf4-switch__hint">Aparecerá resaltado en el catálogo público.</span>
            </span>
          </label>
        </CollapsibleSection>

        <CollapsibleSection title="Precios y stock" icon="fa-coins">
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-buy">Precio compra (₡) *</label>
              <input id="p-buy" type="number" min={0} step="0.01" value={form.purchase_price} onChange={(e) => set('purchase_price', e.target.value)} required />
              {errors.purchase_price ? <div className="field-error">{errors.purchase_price}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="p-sell">Precio venta (₡) *</label>
              <input id="p-sell" type="number" min={0} step="0.01" value={form.sale_price} onChange={(e) => set('sale_price', e.target.value)} required />
              {errors.sale_price ? <div className="field-error">{errors.sale_price}</div> : null}
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-stock">Stock actual *</label>
              <input id="p-stock" type="number" min={0} value={form.stock_current} onChange={(e) => set('stock_current', e.target.value)} required />
              {errors.stock_current ? <div className="field-error">{errors.stock_current}</div> : null}
            </div>
            <div className="form-group">
              <label htmlFor="p-stockmin">Stock mínimo *</label>
              <input id="p-stockmin" type="number" min={0} value={form.stock_minimum} onChange={(e) => set('stock_minimum', e.target.value)} required />
              {errors.stock_minimum ? <div className="field-error">{errors.stock_minimum}</div> : null}
            </div>
          </div>
        </CollapsibleSection>

        <CollapsibleSection title="Imágenes" icon="fa-images" defaultOpen={false}>
          <div className="form-row">
            <div className="form-group">
              <label htmlFor="p-image">Imagen principal</label>
              <FileUpload
                id="p-image"
                label="Arrastra una imagen o haz clic para seleccionar"
                hint="JPG, PNG o WEBP"
                accept="image/*"
                icon="fa-image"
                previewUrl={currentImage}
                onChange={(files) => setMainImage(files?.[0] ?? null)}
              />
            </div>
            <div className="form-group">
              <label htmlFor="p-gallery">Imágenes adicionales (carrusel)</label>
              <FileUpload
                id="p-gallery"
                label="Arrastra varias imágenes o haz clic"
                hint="Puedes seleccionar varias"
                accept="image/*"
                icon="fa-images"
                multiple
                onChange={(files) => setGallery(files)}
              />
            </div>
          </div>
        </CollapsibleSection>

        {attributes.length > 0 || editingId !== null ? (
          <CollapsibleSection title="Clasificación" icon="fa-tags" description="Atributos y variantes del producto." defaultOpen={false}>
            {attributes.length > 0 ? (
              <div className="form-row">
                {attributes.map((attribute) => (
                  <div className="form-group" key={attribute.id}>
                    <label htmlFor={`attr-${attribute.id}`}>{attribute.label}</label>
                    <select
                      id={`attr-${attribute.id}`}
                      value={selectedValues[attribute.id] ?? ''}
                      onChange={(e) => setSelectedValues((current) => ({ ...current, [attribute.id]: e.target.value }))}
                    >
                      <option value="">— Ninguno —</option>
                      {attribute.values.map((value) => (
                        <option key={value.id} value={String(value.id)}>{value.value}</option>
                      ))}
                    </select>
                  </div>
                ))}
              </div>
            ) : null}
            {editingId !== null ? (
              <div className="cf4-variants-block">
                <h4 className="cf4-variants-block__title">Variantes</h4>
                <VariantsManager baseProductId={editingId} csrfToken={csrfToken} />
              </div>
            ) : null}
          </CollapsibleSection>
        ) : null}
      </form>
    </Modal>
  );
}
