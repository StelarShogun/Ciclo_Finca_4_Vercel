import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('Usuarios (Inertia): lista, filtros y orden', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/clientes', { waitUntil: 'networkidle' });
  expect(res?.status()).toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');

  await expect(page.locator('h1')).toContainText('Usuarios');
  expect(await page.locator('.clients-table tbody tr').count()).toBeGreaterThan(0);
  await expect(page.locator('.cf4-filters')).toBeVisible();

  watcher.assert(testInfo);
});
