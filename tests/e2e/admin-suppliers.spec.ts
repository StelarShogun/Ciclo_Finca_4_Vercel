import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('Proveedores (Inertia) carga lista, KPIs y abre modal de alta', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/suppliers', { waitUntil: 'networkidle' });
  expect(res?.status(), 'GET /suppliers debe responder 2xx').toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');

  await expect(page.locator('h1')).toContainText('Proveedores');
  expect(await page.locator('.kpi-card').count()).toBeGreaterThanOrEqual(2);
  expect(await page.locator('.suppliers-table tbody tr').count()).toBeGreaterThan(0);

  await page.getByRole('button', { name: /Nuevo proveedor/i }).click();
  await expect(page.locator('#form-supplier')).toBeVisible();
  await expect(page.locator('#sup-email')).toBeVisible();

  watcher.assert(testInfo);
});
