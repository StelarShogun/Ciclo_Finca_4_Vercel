import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';

type Attribute = {
  id: number;
  label: string;
  selected: number | null;
  values: Array<{ id: number; value: string }>;
};

type PageProps = {
  product: {
    product_id: number;
    name: string;
    parent_category: string | null;
    subcategory: string | null;
  };
  attributes: Attribute[];
};

export default function Edit({ attributes, product }: PageProps) {
  const { data, processing, setData, put } = useForm<{ classification_value_ids: string[] }>({
    classification_value_ids: attributes.map((attribute) => (attribute.selected ? String(attribute.selected) : '')),
  });

  function setAttribute(index: number, value: string) {
    setData(
      'classification_value_ids',
      data.classification_value_ids.map((current, i) => (i === index ? value : current)),
    );
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    put(`/products/${product.product_id}/classifications`);
  }

  return (
    <AdminLayout title="Editar características">
      <Head title={`Características: ${product.name}`} />

      <div className="product-classifications-edit">
        <PageHeader title={`Características: ${product.name}`} kicker="Clasificación">
          <p>
            {(product.parent_category ?? '—')} → {(product.subcategory ?? '—')}
          </p>
        </PageHeader>

        <div className="form-card">
          {attributes.length === 0 ? (
            <p className="text-muted">Este tipo de producto no tiene atributos configurados todavía.</p>
          ) : (
            <form onSubmit={submit} className="form-body">
              {attributes.map((attribute, index) => (
                <div className="form-group" key={attribute.id}>
                  <label htmlFor={`attr_${attribute.id}`}>{attribute.label}</label>
                  <select
                    id={`attr_${attribute.id}`}
                    className="form-control"
                    value={data.classification_value_ids[index] ?? ''}
                    onChange={(event) => setAttribute(index, event.target.value)}
                  >
                    <option value="">— Ninguno —</option>
                    {attribute.values.map((value) => (
                      <option key={value.id} value={String(value.id)}>
                        {value.value}
                      </option>
                    ))}
                  </select>
                </div>
              ))}

              <div className="form-actions">
                <button type="submit" className="btn btn-primary" disabled={processing}>
                  {processing ? 'Guardando…' : 'Guardar'}
                </button>
                <Link href="/product-classifications" className="btn btn-secondary">
                  Cancelar
                </Link>
              </div>
            </form>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
