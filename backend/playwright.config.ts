import { defineConfig, devices } from '@playwright/test';

/**
 * Suite E2E contra PRODUCCIÓN (Vercel).
 * - El login admin tiene reCAPTCHA, así que la sesión se captura una sola vez
 *   con el proyecto "setup" (headed, login manual) y se reutiliza vía storageState.
 * - Los specs son no destructivos salvo `import-flow`, que se corre aparte.
 */

const BASE_URL = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';
const STORAGE_STATE = 'tests/e2e/.auth/admin.json';

export default defineConfig({
  testDir: './tests/e2e',
  // Producción: no la martillamos en paralelo.
  workers: 1,
  fullyParallel: false,
  timeout: 60_000,
  expect: { timeout: 15_000 },
  reporter: [['html', { open: 'never' }], ['list']],
  use: {
    baseURL: BASE_URL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },
  projects: [
    {
      // Login asistido (humano resuelve el captcha). Correr: npm run test:e2e:setup
      name: 'setup',
      testMatch: /auth\.setup\.ts/,
      use: { ...devices['Desktop Chrome'], headless: false },
    },
    {
      // Specs que reutilizan la sesión admin guardada.
      name: 'chromium',
      testIgnore: /auth\.setup\.ts/,
      use: { ...devices['Desktop Chrome'], storageState: STORAGE_STATE },
    },
  ],
});
