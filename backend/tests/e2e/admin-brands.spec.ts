import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('Marcas (Inertia) carga lista y abre modal de nueva marca', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/brands', { waitUntil: 'networkidle' });
  expect(res?.status(), 'GET /brands debe responder 2xx').toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');

  await expect(page.locator('h1')).toContainText('Marcas');

  // La tabla debe renderizar (o el estado vacío).
  const rows = page.locator('.brands-table tbody tr');
  expect(await rows.count()).toBeGreaterThan(0);

  // El modal de nueva marca abre y muestra el campo nombre.
  await page.getByRole('button', { name: /Nueva marca/i }).click();
  await expect(page.locator('#marca-nombre')).toBeVisible();

  watcher.assert(testInfo);
});
