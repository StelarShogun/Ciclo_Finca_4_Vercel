export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export type ToastItem = {
  id: string;
  variant: ToastVariant;
  title: string;
  message?: string;
};

const icons: Record<ToastVariant, string> = {
  success: 'fas fa-check-circle',
  error: 'fas fa-circle-exclamation',
  warning: 'fas fa-triangle-exclamation',
  info: 'fas fa-circle-info',
};

export function ToastContainer({
  onRemove,
  toasts,
}: {
  toasts: ToastItem[];
  onRemove: (id: string) => void;
}) {
  return (
    <div
      className="cf4-toast-region"
      role="region"
      aria-label="Notificaciones"
      aria-live="polite"
      aria-relevant="additions"
    >
      {toasts.map((toast) => (
        <article
          key={toast.id}
          className={`cf4-toast cf4-toast--${toast.variant}`}
          role={toast.variant === 'error' || toast.variant === 'warning' ? 'alert' : 'status'}
          aria-atomic="true"
        >
          <div className="cf4-toast__icon" aria-hidden="true">
            <i className={icons[toast.variant]} />
          </div>

          <div className="cf4-toast__body">
            <strong className="cf4-toast__title">{toast.title}</strong>
            {toast.message ? <p className="cf4-toast__message">{toast.message}</p> : null}
          </div>

          <button
            type="button"
            className="cf4-toast__close"
            aria-label="Cerrar alerta"
            onClick={() => onRemove(toast.id)}
          >
            <i className="fas fa-times" aria-hidden="true" />
          </button>
        </article>
      ))}
    </div>
  );
}

