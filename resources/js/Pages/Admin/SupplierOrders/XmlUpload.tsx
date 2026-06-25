import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { AdminLayout } from '@/shared/components/layout/AdminLayout';
import { PageHeader } from '@/shared/components/ui/PageHeader';
import { FileUpload } from '@/shared/components/ui/FileUpload';

import '../../../../css/admin/orders/orders.css';

export default function XmlUpload() {
  const { data, setData, post, processing, errors } = useForm<{ xml_file: File | null; threshold: number }>({
    xml_file: null,
    threshold: 10,
  });
  const [clientError, setClientError] = useState('');

  function submit(event: FormEvent) {
    event.preventDefault();
    if (!data.xml_file) {
      setClientError('Debes seleccionar un archivo XML antes de analizar.');
      return;
    }
    setClientError('');
    post('/supplier-orders/xml-deviation/analyse', { forceFormData: true });
  }

  return (
    <AdminLayout title="Importar XML de proveedor">
      <Head title="Importar XML de proveedor – Admin" />

      <div className="sales-container xml-upload-page">
        <div className="xml-upload-shell">
          <PageHeader
            title="Importar XML de proveedor"
            kicker="Proveedores"
            actions={<a href="/supplier-orders" className="btn btn-secondary btn-sm"><i className="fas fa-arrow-left" aria-hidden="true" /> Volver a pedidos</a>}
          >
            <p>Carga un archivo XML del proveedor para comparar los precios de compra actuales antes de aplicar cambios.</p>
          </PageHeader>

          <nav className="reports-breadcrumb" aria-label="Migas de pan">
            <a href="/supplier-orders">Pedidos a proveedor</a>
            <span className="sep">/</span>
            <span>Importar XML</span>
          </nav>

          <div className="xml-upload-card">
            <h2><i className="fas fa-file-import xml-upload-card__icon" aria-hidden="true" />Seleccionar archivo XML</h2>
            <p className="subtitle">
              El sistema comparará los precios del XML contra el precio de compra actual de cada producto y le mostrará las diferencias antes de aplicar cualquier cambio.
            </p>

            {clientError ? <div className="xml-form-error" role="alert">{clientError}</div> : null}

            <form onSubmit={submit} noValidate>
              <div className="xml-field-group">
                <div>
                  <label htmlFor="xml_file">Archivo XML del proveedor</label>
                  <FileUpload
                    id="xml_file"
                    label="Arrastra el XML o haz clic para seleccionar"
                    hint="Tamaño máximo: 5 MB. Solo archivos .xml."
                    icon="fa-file-code"
                    accept=".xml,text/xml,application/xml"
                    onChange={(files) => setData('xml_file', files?.[0] ?? null)}
                  />
                  {errors.xml_file ? <p className="xml-field-error" role="alert">{errors.xml_file}</p> : null}
                </div>

                <div>
                  <label htmlFor="threshold">Umbral de desvío (%) <span style={{ color: 'var(--color-danger)' }}>*</span></label>
                  <input
                    id="threshold"
                    type="number"
                    value={data.threshold}
                    min={0}
                    max={100}
                    step={0.5}
                    required
                    onChange={(e) => setData('threshold', parseFloat(e.target.value) || 0)}
                  />
                  <p className="field-hint">
                    Variación mínima para marcar un producto como desvío. Por ejemplo, <strong>10</strong> significa que sólo se resaltarán productos con un cambio de precio ≥ 10%.
                  </p>
                  {errors.threshold ? <p className="xml-field-error" role="alert">{errors.threshold}</p> : null}
                </div>
              </div>

              <div className="xml-actions">
                <button type="submit" className="btn btn-primary" disabled={processing}>
                  <i className={`fas ${processing ? 'fa-spinner fa-spin' : 'fa-search'}`} aria-hidden="true" /> {processing ? 'Procesando…' : 'Analizar XML'}
                </button>
                <a href="/supplier-orders" className="btn btn-ghost">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
