import { useEffect, useState } from 'react';

import { Modal } from '@/shared/components/ui/Modal';

import type { SupplierDetail } from '../types';

const STATUS_LABELS: Record<string, string> = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido' };

type Props = {
  supplierId: number | null;
  onClose: () => void;
};

export function ViewSupplierModal({ supplierId, onClose }: Props) {
  const [supplier, setSupplier] = useState<SupplierDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (supplierId == null) {
      setSupplier(null);
      return;
    }
    let active = true;
    setLoading(true);
    setError(false);
    setSupplier(null);
    fetch(`/supplier/details/${supplierId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.json())
      .then((data) => {
        if (!active) return;
        if (data.success && data.supplier) setSupplier(data.supplier);
        else setError(true);
      })
      .catch(() => active && setError(true))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [supplierId]);

  const stars = supplier ? '★'.repeat(Math.round(supplier.rating)) + '☆'.repeat(5 - Math.round(supplier.rating)) : '';

  return (
    <Modal
      isOpen={supplierId != null}
      onClose={onClose}
      className="cf4-modal cf4-modal--wide"
      title={<><i className="fas fa-truck" aria-hidden="true" /> Datos del proveedor</>}
      footer={<button type="button" className="btn btn-secondary" onClick={onClose}><i className="fas fa-times" aria-hidden="true" /> Cerrar</button>}
    >
      {loading ? (
        <div className="loading-spinner" role="status"><i className="fas fa-spinner fa-spin fa-2x" aria-hidden="true" /><p>Cargando datos del proveedor…</p></div>
      ) : error || !supplier ? (
        <div className="alert alert-danger"><i className="fas fa-exclamation-circle" aria-hidden="true" /> No se pudieron cargar los datos del proveedor.</div>
      ) : (
        <div className="sale-details">
          <div className="detail-section">
            <h4><i className="fas fa-truck" aria-hidden="true" /> Datos del proveedor</h4>
            <div className="detail-grid">
              <div className="detail-item"><label>Nombre:</label><span><strong>{supplier.name}</strong></span></div>
              <div className="detail-item"><label>Contacto:</label><span>{supplier.primary_contact || '—'}</span></div>
              <div className="detail-item"><label>Teléfono:</label><span>{supplier.phone || '—'}</span></div>
              <div className="detail-item"><label>Correo:</label><span>{supplier.email || '—'}</span></div>
              <div className="detail-item"><label>Dirección:</label><span>{supplier.address || '—'}</span></div>
              <div className="detail-item"><label>Tiempo de entrega:</label><span>{supplier.delivery_time} día(s)</span></div>
              <div className="detail-item"><label>Evaluación:</label><span title={`${supplier.rating}/5`}>{stars} ({supplier.rating})</span></div>
              <div className="detail-item"><label>Estado:</label><span className={`status-badge ${supplier.status}`}>{STATUS_LABELS[supplier.status] || supplier.status}</span></div>
              <div className="detail-item"><label>Productos activos:</label><span>{supplier.products_count}</span></div>
            </div>
          </div>
        </div>
      )}
    </Modal>
  );
}
