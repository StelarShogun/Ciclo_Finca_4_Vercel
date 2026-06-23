import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('modal Ver carga los datos del producto', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  await page.goto('/inventory', { waitUntil: 'networkidle' });

  const viewBtn = page.locator('.view-details-btn').first();
  await expect(viewBtn).toBeVisible();
  const productId = await viewBtn.getAttribute('data-product-id');
  expect(productId, 'el botón Ver debe tener data-product-id').toBeTruthy();

  // Esperamos la respuesta JSON del producto al abrir el modal.
  const [resp] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes(`/products/${productId}`) && r.request().method() === 'GET',
      { timeout: 30_000 },
    ),
    viewBtn.click(),
  ]);

  expect(resp.status(), `GET /products/${productId} debe ser 200`).toBe(200);
  const json = await resp.json().catch(() => null);
  expect(json?.success, 'la respuesta debe traer success:true').toBeTruthy();
  expect(json?.data?.name, 'el producto debe traer nombre').toBeTruthy();

  // El modal de detalle debe mostrarse con contenido real.
  const modal = page.locator('#view-product-modal');
  await expect(modal).toBeVisible();
  const body = page.locator('#view-product-body');
  await expect(body).not.toBeEmpty();
  await expect(body).toContainText(String(json.data.name).slice(0, 8));

  watcher.assert(testInfo);
});
