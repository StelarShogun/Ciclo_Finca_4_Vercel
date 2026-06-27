type EmptyStateProps = {
  title: string;
  description?: string;
};

export function EmptyState({ description, title }: EmptyStateProps) {
  return (
    <div className="cart-empty">
      <div className="cart-empty-inner">
        <div className="cart-empty-icon" aria-hidden="true">
          <i className="fas fa-circle-info" />
        </div>
        <h2 className="cart-empty-title">{title}</h2>
        {description ? <p className="cart-empty-text">{description}</p> : null}
      </div>
    </div>
  );
}
