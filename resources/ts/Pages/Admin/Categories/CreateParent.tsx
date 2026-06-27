import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { useFlashToasts } from '@/shared/hooks/useFlashToasts';

export default function CreateParent() {
  useFlashToasts();
  const { data, errors, processing, reset, setData, post } = useForm({ name: '', description: '' });

  function submit(event: FormEvent) {
    event.preventDefault();
    post('/categories/parents', { onSuccess: () => reset('name', 'description') });
  }

  return (
    <AdminLayout title="Nueva categoría">
      <Head title="Nueva categoría - Ciclo Finca 4 Admin" />

      <div className="form-container">
        <PageHeader title="Nueva categoría padre" kicker="Categorías">
          <p>Las categorías padre agrupan a las subcategorías (tipos concretos).</p>
        </PageHeader>

        <div className="form-card">
          <form onSubmit={submit} className="form-body">
            <div className="form-group">
              <label htmlFor="name">Nombre de la categoría *</label>
              <input
                id="name"
                type="text"
                value={data.name}
                onChange={(event) => setData('name', event.target.value)}
                required
              />
              {errors.name ? <div className="field-error">{errors.name}</div> : null}
            </div>

            <div className="form-group">
              <label htmlFor="description">Descripción</label>
              <textarea
                id="description"
                rows={3}
                value={data.description}
                onChange={(event) => setData('description', event.target.value)}
              />
              {errors.description ? <div className="field-error">{errors.description}</div> : null}
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={processing}>
                {processing ? 'Guardando…' : 'Crear categoría'}
              </button>
              <Link href="/inventory" className="btn btn-secondary">
                Cancelar
              </Link>
            </div>
          </form>
        </div>
      </div>
    </AdminLayout>
  );
}
