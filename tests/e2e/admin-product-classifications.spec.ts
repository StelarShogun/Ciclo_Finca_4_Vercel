import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('Características por producto (Inertia): lista y edición', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/product-classifications', { waitUntil: 'networkidle' });
  expect(res?.status()).toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');
  await expect(page.locator('h1')).toContainText('Características por producto');

  const editLink = page.locator('a.action-btn.edit').first();
  const href = await editLink.getAttribute('href');
  expect(href).toMatch(/\/products\/\d+\/classifications\/edit/);

  // Carga directa de la página de edición (evita el rehydrate de versión post-deploy).
  await page.goto(href!, { waitUntil: 'networkidle' });
  await expect(page.locator('h1')).toContainText('Características:');
  await expect(page.getByRole('button', { name: /Guardar/i })).toBeVisible();

  watcher.assert(testInfo);
});
