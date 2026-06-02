import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

import '../css/client/toasts.css';
import { ToastProvider } from '@/Components/UI/ToastProvider';

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
    createRoot(el).render(
      <ToastProvider>
        <App {...props} />
      </ToastProvider>,
    );
  },
  progress: {
    color: '#235347',
  },
});
