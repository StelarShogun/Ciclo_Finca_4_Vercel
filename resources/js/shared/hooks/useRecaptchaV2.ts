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
    script.src = 'https://www.google.com/recaptcha/api';
    script.async = true;
    script.defer = true;
    script.dataset.cf4RecaptchaApi = '1';
    script.addEventListener('load', () => resolve());
    script.addEventListener('error', () => reject(new Error('Failed to load reCAPTCHA api')));
    document.head.appendChild(script);
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
