import { useEffect, useRef, useState } from 'react';
import { uploadPresigned } from '@vercel/blob/client';

import { Modal } from '@/shared/components/ui/Modal';
import { useToast } from '@/shared/hooks/useToast';

type ImportModalProps = {
  open: boolean;
  blobUploadUrl: string;
  csrfToken: string;
  onClose: () => void;
  onFinished: () => void;
};

type Progress = {
  status: string;
  message?: string;
  created?: number;
  updated?: number;
  skipped?: number;
  errors?: number;
};

const TERMINAL = ['done', 'failed', 'cancelled'];

function safeName(name: string) {
  return (name || 'import.dat').replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, '') || 'import.dat';
}

export function ImportModal({ blobUploadUrl, csrfToken, onClose, onFinished, open }: ImportModalProps) {
  const { showToast } = useToast();
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [statusLabel, setStatusLabel] = useState('');
  const [progress, setProgress] = useState<Progress | null>(null);
  const [importId, setImportId] = useState<string | null>(null);
  const pollRef = useRef<number | null>(null);

  useEffect(() => {
    return () => {
      if (pollRef.current) {
        window.clearInterval(pollRef.current);
      }
    };
  }, []);

  function startPolling(id: string) {
    if (pollRef.current) {
      window.clearInterval(pollRef.current);
    }
    pollRef.current = window.setInterval(async () => {
      try {
        const response = await fetch(`/inventory/import/${id}/progress`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const p = (await response.json()) as Progress;
        setProgress(p);
        if (TERMINAL.includes(p.status)) {
          if (pollRef.current) window.clearInterval(pollRef.current);
          setBusy(false);
          if (p.status === 'done') {
            showToast({ variant: 'success', title: 'Importación completada', message: p.message });
            onFinished();
          } else if (p.status === 'failed') {
            showToast({ variant: 'error', title: 'Importación fallida', message: p.message });
          }
        }
      } catch {
        /* reintenta en el próximo tick */
      }
    }, 1500);
  }

  async function startImport() {
    if (!file) {
      showToast({ variant: 'error', title: 'Sin archivo', message: 'Seleccioná un archivo para importar.' });
      return;
    }
    setBusy(true);
    setStatusLabel('Enviando…');
    try {
      let response: Response;
      if (blobUploadUrl) {
        setStatusLabel('Subiendo archivo…');
        const blob = await uploadPresigned(`catalog-imports/browser-${Date.now()}-${safeName(file.name)}`, file, {
          access: 'public',
          handleUploadUrl: blobUploadUrl,
          contentType: file.type || 'application/octet-stream',
          clientPayload: JSON.stringify({ originalName: file.name, size: file.size }),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        response = await fetch('/inventory/import', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ blob_path: blob.pathname, blob_url: blob.url, original_name: file.name }),
        });
      } else {
        const body = new FormData();
        body.append('import_file', file);
        response = await fetch('/inventory/import', {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
          body,
        });
      }

      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        setBusy(false);
        setStatusLabel('');
        showToast({
          variant: 'error',
          title: response.status === 422 ? 'Archivo no válido' : 'Importación fallida',
          message: data.message ?? 'No se pudo iniciar la importación.',
        });
        return;
      }

      setImportId(data.importId);
      setProgress(data.progress ?? { status: 'queued', message: 'En cola…' });
      setStatusLabel('Procesando…');
      startPolling(data.importId);
    } catch (error) {
      setBusy(false);
      setStatusLabel('');
      showToast({
        variant: 'error',
        title: 'Error',
        message: (error as Error)?.message || 'Error al iniciar la importación.',
      });
    }
  }

  async function cancelImport() {
    if (!importId) return;
    await fetch(`/inventory/import/${importId}/cancel`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
    }).catch(() => undefined);
  }

  function reset() {
    setFile(null);
    setProgress(null);
    setImportId(null);
    setStatusLabel('');
    setBusy(false);
  }

  return (
    <Modal
      isOpen={open}
      onClose={() => {
        reset();
        onClose();
      }}
      title="Importar productos"
      footer={
        progress && !TERMINAL.includes(progress.status) ? (
          <button type="button" className="btn btn-danger-soft" onClick={cancelImport}>
            Cancelar importación
          </button>
        ) : (
          <>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => {
                reset();
                onClose();
              }}
            >
              Cerrar
            </button>
            <button type="button" className="btn btn-primary" onClick={startImport} disabled={busy || !file}>
              {busy ? statusLabel || 'Procesando…' : 'Importar'}
            </button>
          </>
        )
      }
    >
      <p className="import-modal-intro">
        Acepta CSV, XML, JSON o un ZIP exportado (con imágenes). Máx. 100 MB.
      </p>
      {!progress ? (
        <div className="form-group">
          <input
            type="file"
            accept=".zip,.xml,.csv,.txt,.json"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          />
        </div>
      ) : null}

      {progress ? (
        <div className="import-progress" aria-live="polite">
          <p className="import-progress__message">
            <strong>{progress.status}</strong> — {progress.message ?? ''}
          </p>
          <div className="import-progress__stats">
            <span>{progress.created ?? 0} creados</span>
            <span>{progress.updated ?? 0} actualizados</span>
            <span>{progress.skipped ?? 0} omitidos</span>
            <span>{progress.errors ?? 0} errores</span>
          </div>
        </div>
      ) : statusLabel ? (
        <p className="text-muted">{statusLabel}</p>
      ) : null}
    </Modal>
  );
}
