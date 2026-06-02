type LoadingStateProps = {
  label?: string;
};

export function LoadingState({ label = 'Cargando...' }: LoadingStateProps) {
  return (
    <div className="loading-state" role="status" aria-live="polite">
      <i className="fas fa-spinner fa-spin" aria-hidden="true" />
      <span>{label}</span>
    </div>
  );
}
