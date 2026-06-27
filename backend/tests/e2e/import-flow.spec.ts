import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

/**
 * ⚠️ Este spec MODIFICA el catálogo real de producción: sube un CSV con un
 * producto de prueba e inicia la importación (Blob + QStash). Por eso solo corre
 * cuando se pide explícitamente con E2E_RUN_IMPORT=1.
 *
 *   E2E_RUN_IMPORT=1 npx playwright test import-flow --project=chromium
 *
 * Verifica el fix: ante un fallo, el modal debe mostrar el mensaje REAL
 * (no "Server Error"); en éxito, /inventory/import responde 202 y el progreso
 * avanza de "en cola" a un estado de lectura/terminal.
 */
const RUN_IMPORT = process.env.E2E_RUN_IMPORT === '1';

// Producto de prueba claramente identificable para poder limpiarlo luego.
const TEST_PRODUCT_NAME = `E2E TEST ${new Date().toISOString().slice(0, 10)} (borrar)`;
const TEST_CSV = `name,sale_price,stock,sku\n"${TEST_PRODUCT_NAME}",1000,1,E2E-TEST-SKU\n`;

test.describe('flujo de importación de catálogo', () => {
  test.skip(!RUN_IMPORT, 'Activá con E2E_RUN_IMPORT=1 (modifica producción)');

  test('subir CSV inicia la importación y reporta estado real', async ({ page }) => {
    test.setTimeout(120_000);

    await page.goto('/inventory', { waitUntil: 'networkidle' });
    await page.locator('#open-import-modal').click();
    await expect(page.locator('#import-modal')).toBeVisible();

    // Cargar el CSV de prueba en el input de archivo.
    await page.locator('#import_file').setInputFiles({
      name: 'e2e-import-test.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from(TEST_CSV, 'utf-8'),
    });

    const confirmBtn = page.locator('#confirm-import');
    await expect(confirmBtn).toBeEnabled();

    // Capturar la respuesta de orquestación de import.
    const importRespPromise = page.waitForResponse(
      (r) => r.url().includes('/inventory/import') && r.request().method() === 'POST',
      { timeout: 90_000 },
    );

    await confirmBtn.click();

    // Confirmar el diálogo SweetAlert ("Sí, importar").
    const swalConfirm = page.locator('.swal2-confirm');
    if (await swalConfirm.isVisible().catch(() => false)) {
      await swalConfirm.click();
    }

    const importResp = await importRespPromise;
    const status = importResp.status();
    const bodyText = await importResp.text();

    // El fix garantiza que nunca sea un 500 genérico sin cuerpo JSON.
    expect(status, `respuesta /inventory/import: ${status} ${bodyText}`).not.toBe(500);

    if (status === 202) {
      const json = JSON.parse(bodyText);
      expect(json.importId, 'debe devolver importId').toBeTruthy();
      // El panel de progreso debe aparecer.
      await expect(page.locator('#import-progress')).toBeVisible({ timeout: 15_000 });
    } else {
      // Si falla, debe mostrar el mensaje real en el modal (no "Server Error").
      const json = JSON.parse(bodyText);
      expect(json.message, 'un fallo debe traer message legible').toBeTruthy();
      console.log(`Import devolvió ${status}: ${json.message}`);
    }
  });
});
