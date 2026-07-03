import type { Page, TestInfo } from '@playwright/test';

/**
 * Engancha listeners a la página para detectar "productos que no cargan":
 * errores de consola y respuestas HTTP >= 400. Devuelve un assert() que se
 * llama al final del test para fallar si hubo problemas.
 *
 * Se ignoran respuestas externas (analytics, recaptcha, fuentes) y solo se
 * vigilan llamadas a la propia app.
 */
const IGNORED_URL_PATTERNS = [
  /google\.com\/recaptcha/i,
  /gstatic\.com/i,
  /googletagmanager|google-analytics|analytics/i,
  /fonts\.(googleapis|gstatic)\.com/i,
  /\.(png|jpe?g|gif|svg|webp|woff2?|ttf|ico)(\?|$)/i,
];

const IGNORED_CONSOLE_PATTERNS = [
  /recaptcha/i,
  /favicon/i,
  /Failed to load resource.*(gstatic|recaptcha)/i,
];

export function watchForErrors(page: Page, appBaseUrl: string) {
  const consoleErrors: string[] = [];
  const badResponses: string[] = [];

  page.on('console', (msg) => {
    if (msg.type() !== 'error') return;
    const text = msg.text();
    if (IGNORED_CONSOLE_PATTERNS.some((re) => re.test(text))) return;
    consoleErrors.push(text);
  });

  page.on('pageerror', (err) => {
    consoleErrors.push(`pageerror: ${err.message}`);
  });

  page.on('response', (res) => {
    const url = res.url();
    if (!url.startsWith(appBaseUrl)) return;
    if (IGNORED_URL_PATTERNS.some((re) => re.test(url))) return;
    if (res.status() >= 400) {
      badResponses.push(`${res.status()} ${res.request().method()} ${url}`);
    }
  });

  return {
    assert(testInfo?: TestInfo) {
      const problems: string[] = [];
      if (consoleErrors.length) problems.push(`Errores de consola:\n  - ${consoleErrors.join('\n  - ')}`);
      if (badResponses.length) problems.push(`Respuestas HTTP fallidas:\n  - ${badResponses.join('\n  - ')}`);
      if (problems.length) {
        const message = problems.join('\n');
        if (testInfo) testInfo.annotations.push({ type: 'errors-detected', description: message });
        throw new Error(message);
      }
    },
    consoleErrors,
    badResponses,
  };
}
