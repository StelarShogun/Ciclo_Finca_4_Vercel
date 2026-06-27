import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('dashboard carga sin errores con sesión admin', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/dashboard', { waitUntil: 'networkidle' });
  expect(res?.status(), 'GET /dashboard debe responder 2xx').toBeLessThan(400);

  // No fuimos redirigidos al login (sesión válida).
  expect(page.url()).not.toContain('/admin/login');

  await expect(page.locator('body')).toBeVisible();
  watcher.assert(testInfo);
});
