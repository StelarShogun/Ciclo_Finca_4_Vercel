type LoadingStateProps = {
  label?: string;
};

export function LoadingState({ label = 'Cargando...' }: LoadingStateProps) {
  return (
    <output className="loading-state" aria-live="polite">
      <i className="fas fa-spinner fa-spin" aria-hidden="true" />
      <span>{label}</span>
    </output>
  );
}
