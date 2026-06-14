import type { RefObject } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';

type UseRecaptchaV2Result = {
  widgetRef: RefObject<HTMLDivElement | null>;
  token: string;
  isRendered: boolean;
};

declare global {
  interface Window {
    grecaptcha?: {
      render: (el: HTMLElement, options: Record<string, unknown>) => number | string;
      ready?: (cb: () => void) => void;
    };
  }
}

function getCurrentThemeForRecaptcha(): 'light' | 'dark' {
  return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

function loadRecaptchaScript(): Promise<void> {
  const existing = document.querySelector<HTMLScriptElement>('script[data-cf4-recaptcha-api="1"]');
  if (existing) {
    return new Promise((resolve) => {
      existing.addEventListener('load', () => resolve());
      window.setTimeout(() => resolve(), 0);
    });
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    // Debe ser api.js (con extensión) y render=explicit para usar grecaptcha.render manualmente.
    script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
    script.async = true;
    script.defer = true;
    script.dataset.cf4RecaptchaApi = '1';
    script.addEventListener('load', () => resolve());
    script.addEventListener('error', () => reject(new Error('Failed to load reCAPTCHA api')));
    document.head.appendChild(script);
  });
}

/** Espera a que grecaptcha esté listo (la API se inicializa de forma asíncrona tras cargar el script). */
function whenRecaptchaReady(): Promise<void> {
  return new Promise((resolve) => {
    const start = Date.now();
    const check = () => {
      const g = window.grecaptcha;
      if (g && typeof g.render === 'function') {
        if (typeof g.ready === 'function') {
          g.ready(() => resolve());
        } else {
          resolve();
        }
        return;
      }
      if (Date.now() - start > 10_000) {
        resolve();
        return;
      }
      window.setTimeout(check, 100);
    };
    check();
  });
}

export function useRecaptchaV2(siteKey: string | null | undefined): UseRecaptchaV2Result {
  const widgetRef = useRef<HTMLDivElement>(null);
  const [token, setToken] = useState('');
  const [isRendered, setIsRendered] = useState(false);

  const theme = useMemo(() => (typeof document === 'undefined' ? 'light' : getCurrentThemeForRecaptcha()), []);

  useEffect(() => {
    if (!siteKey) return;

    let cancelled = false;

    (async () => {
      try {
        await loadRecaptchaScript();
        await whenRecaptchaReady();
        if (cancelled) return;
        if (!widgetRef.current) return;
        if (!window.grecaptcha) return;
        if (widgetRef.current.dataset.cf4RecaptchaRendered === '1') return;

        window.grecaptcha.render(widgetRef.current, {
          sitekey: siteKey,
          theme,
          callback: (t: string) => setToken(t),
          'expired-callback': () => setToken(''),
        });

        widgetRef.current.dataset.cf4RecaptchaRendered = '1';
        setIsRendered(true);
      } catch {
        setIsRendered(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [siteKey, theme]);

  return { widgetRef, token, isRendered };
}
