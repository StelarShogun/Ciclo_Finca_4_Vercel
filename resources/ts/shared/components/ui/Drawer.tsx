import type { PropsWithChildren, ReactNode } from 'react';
import { useEffect } from 'react';

type DrawerProps = PropsWithChildren<{
  isOpen: boolean;
  onClose: () => void;
  title: ReactNode;
  footer?: ReactNode;
  className?: string;
  overlayClassName?: string;
  closeLabel?: string;
  id?: string;
}>;

export function Drawer({
  children,
  className = 'cf4-favorites-drawer',
  closeLabel = 'Cerrar panel',
  footer,
  id = 'favorites-drawer',
  isOpen,
  onClose,
  overlayClassName = 'cf4-favorites-overlay',
  title,
}: DrawerProps) {
  useEffect(() => {
    if (!isOpen) {
      return;
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
    }

    document.addEventListener('keydown', onKeyDown);

    return () => document.removeEventListener('keydown', onKeyDown);
  }, [isOpen, onClose]);

  return (
    <>
      <div
        className={overlayClassName}
        id={`${id}-overlay`}
        hidden={!isOpen}
        onClick={onClose}
        aria-hidden={!isOpen}
      />
      <aside
        className={`${className}${isOpen ? ' is-open' : ''}`.trim()}
        id={id}
        aria-hidden={!isOpen}
      >
        <div className="cf4-favorites-drawer-header">
          <h3>{title}</h3>
          <button type="button" aria-label={closeLabel} onClick={onClose}>
            <i className="fas fa-times" />
          </button>
        </div>
        <div className="cf4-favorites-drawer-body">{children}</div>
        {footer ? <footer className="cf4-favorites-drawer-footer">{footer}</footer> : null}
      </aside>
    </>
  );
}
