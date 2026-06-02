import { useEffect, type PropsWithChildren } from 'react';

import { useFlashToasts } from '@/hooks/useFlashToasts';

import '../../css/client/fonts.css';
import '../../css/client/fontawesome.css';
import '../../css/client/variables-reset.css';
import '../../css/client/clients-users.css';

export function ClientAuthLayout({ children }: PropsWithChildren) {
  useEffect(() => {
    document.body.classList.add('cliente-layout');
    return () => {
      document.body.classList.remove('cliente-layout');
    };
  }, []);

  useFlashToasts();

  return <main className="cliente-main">{children}</main>;
}

