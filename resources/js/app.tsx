import './bootstrap';
import '../css/client/fonts.css';
import '../css/client/fontawesome.css';
import '../css/client/variables-reset.css';
import '../css/client/header.css';
import '../css/client/footer.css';
import '../css/client/clients-page.css';
import '../css/client/legal-pages.css';
import '../css/admin/shell-base.css';
import '../css/admin/components/page-header.css';
import '../css/admin/dashboard/dashboard.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

const pages = import.meta.glob<{ default: ComponentType }>(
  './Pages/**/*.tsx',
);

createInertiaApp({
  resolve: async (name) => {
    const importPage = pages[`./Pages/${name}.tsx`];

    if (!importPage) {
      throw new Error(`Inertia page not found: ${name}`);
    }

    return (await importPage()).default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#235347',
  },
});
