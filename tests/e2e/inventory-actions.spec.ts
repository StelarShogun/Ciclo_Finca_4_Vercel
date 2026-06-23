import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test.describe('acciones de inventario (no destructivas)', () => {
  test('modal de ajuste de stock abre con datos del producto', async ({ page }, testInfo) => {
    const watcher = watchForErrors(page, BASE);
    await page.goto('/inventory', { waitUntil: 'networkidle' });

    const addStock = page.locator('[data-stock-action="add"]').first();
    await expect(addStock).toBeVisible();
    await addStock.click();

    const stockModal = page.locator('#stock-adjust-modal');
    await expect(stockModal).toBeVisible();
    // El nombre del producto debe precargarse en el modal.
    await expect(page.locator('#stock-modal-product-name')).not.toBeEmpty();

    watcher.assert(testInfo);
  });

  test('menú Exportar abre y ofrece formatos', async ({ page }, testInfo) => {
    const watcher = watchForErrors(page, BASE);
    await page.goto('/inventory', { waitUntil: 'networkidle' });

    await page.locator('#inventory-export-toggle').click();
    const menu = page.locator('#inventory-export-menu');
    await expect(menu).toBeVisible();

    // Los enlaces de exportación deben apuntar a /inventory/export/...
    const exportLinks = menu.locator('a[href*="/inventory/export"]');
    expect(await exportLinks.count()).toBeGreaterThan(0);

    watcher.assert(testInfo);
  });

  test('modal Importar abre y queda listo para subir', async ({ page }, testInfo) => {
    const watcher = watchForErrors(page, BASE);
    await page.goto('/inventory', { waitUntil: 'networkidle' });

    await page.locator('#open-import-modal').click();
    const importModal = page.locator('#import-modal');
    await expect(importModal).toBeVisible();
    await expect(importModal.locator('#import-form')).toBeVisible();
    await expect(importModal.locator('#confirm-import')).toBeVisible();

    watcher.assert(testInfo);
  });
});
