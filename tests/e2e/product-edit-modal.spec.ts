import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('modal Editar precarga los datos del producto (sin guardar)', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  await page.goto('/inventory', { waitUntil: 'networkidle' });

  const editBtn = page.locator('.edit-btn').first();
  await expect(editBtn).toBeVisible();
  const productId = await editBtn.getAttribute('data-product-id');
  expect(productId).toBeTruthy();

  const [resp] = await Promise.all([
    page.waitForResponse(
      (r) => r.url().includes(`/products/${productId}`) && r.request().method() === 'GET',
      { timeout: 30_000 },
    ),
    editBtn.click(),
  ]);

  expect(resp.status(), `GET /products/${productId} debe ser 200`).toBe(200);
  const json = await resp.json().catch(() => null);
  expect(json?.success).toBeTruthy();

  // El form de edición debe quedar activo y apuntar al producto correcto.
  const modal = page.locator('#edit-modal');
  await expect(modal).toHaveClass(/active/);

  const form = page.locator('#edit-product-form');
  await expect(form).toHaveAttribute('action', new RegExp(`/products/${productId}$`));

  // El nombre debe estar precargado con el dato real (no vacío).
  const nameInput = page.locator('#edit-name');
  await expect(nameInput).toHaveValue(/.+/);
  expect(await nameInput.inputValue()).toBe(json.data.name);

  watcher.assert(testInfo);
});
