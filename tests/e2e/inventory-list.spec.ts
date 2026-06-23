import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('inventario lista productos sin errores', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/inventory', { waitUntil: 'networkidle' });
  expect(res?.status(), 'GET /inventory debe responder 2xx').toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');

  // Deben renderizarse filas de productos (o tarjetas en grid view).
  const rows = page.locator('.products-table tbody tr');
  const cards = page.locator('.product-card');
  const rowCount = await rows.count();
  const cardCount = await cards.count();

  expect(rowCount + cardCount, 'Debe haber al menos un producto en el inventario').toBeGreaterThan(0);

  // Al menos un botón de Ver y uno de Editar presentes.
  await expect(page.locator('.view-details-btn').first()).toBeVisible();
  await expect(page.locator('.edit-btn').first()).toBeVisible();

  watcher.assert(testInfo);
});
