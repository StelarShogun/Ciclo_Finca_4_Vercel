"use client";

import { useEffect, useRef, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTheme } from "next-themes";

import { api } from "@/lib/api/client";

/**
 * reCAPTCHA v2 checkbox, port del useRecaptchaV2 viejo (Inertia).
 * El site key viene del backend (GET /api/v1/auth/meta); si es null
 * (dev local sin clave), el widget no se renderiza y el login no lo exige.
 */

declare global {
  interface Window {
    grecaptcha?: {
      render: (el: HTMLElement, options: Record<string, unknown>) => number | string;
      reset: (id?: number | string) => void;
      ready?: (cb: () => void) => void;
    };
  }
}

export function useRecaptchaSiteKey() {
  return useQuery({
    queryKey: ["auth-meta"],
    queryFn: async () => (await api.get("/api/v1/auth/meta")).data.data as { recaptchaSiteKey: string | null },
    staleTime: Infinity,
  });
}

function loadScript(): Promise<void> {
  const existing = document.querySelector<HTMLScriptElement>('script[data-cf4-recaptcha="1"]');
  if (existing) {
    return new Promise((resolve) => {
      existing.addEventListener("load", () => resolve());
      window.setTimeout(() => resolve(), 0);
    });
  }
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = "https://www.google.com/recaptcha/api.js?render=explicit";
    s.async = true;
    s.defer = true;
    s.dataset.cf4Recaptcha = "1";
    s.addEventListener("load", () => resolve());
    s.addEventListener("error", () => reject(new Error("reCAPTCHA api failed")));
    document.head.appendChild(s);
  });
}

function whenReady(): Promise<void> {
  return new Promise((resolve) => {
    const start = Date.now();
    const check = () => {
      const g = window.grecaptcha;
      if (g && typeof g.render === "function") {
        if (typeof g.ready === "function") g.ready(() => resolve());
        else resolve();
        return;
      }
      if (Date.now() - start > 10_000) return resolve();
      window.setTimeout(check, 100);
    };
    check();
  });
}

export function useRecaptchaV2(siteKey: string | null | undefined) {
  const widgetRef = useRef<HTMLDivElement>(null);
  const widgetId = useRef<number | string | null>(null);
  const [token, setToken] = useState("");
  const [isRendered, setIsRendered] = useState(false);
  const { resolvedTheme } = useTheme();

  useEffect(() => {
    if (!siteKey) return;
    let cancelled = false;

    (async () => {
      try {
        await loadScript();
        await whenReady();
        if (cancelled || !widgetRef.current || !window.grecaptcha) return;
        if (widgetRef.current.dataset.rendered === "1") return;

        widgetId.current = window.grecaptcha.render(widgetRef.current, {
          sitekey: siteKey,
          theme: resolvedTheme === "dark" ? "dark" : "light",
          callback: (t: string) => setToken(t),
          "expired-callback": () => setToken(""),
        });
        widgetRef.current.dataset.rendered = "1";
        setIsRendered(true);
      } catch {
        setIsRendered(false);
      }
    })();

    return () => {
      cancelled = true;
    };
    // El theme solo afecta el primer render del widget (igual que el hook viejo).
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [siteKey]);

  function reset() {
    setToken("");
    if (widgetId.current != null) window.grecaptcha?.reset(widgetId.current);
  }

  return { widgetRef, token, isRendered, reset };
}
