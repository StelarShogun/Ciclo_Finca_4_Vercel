import { test, expect } from '@playwright/test';
import { watchForErrors } from './helpers/assert-no-errors';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';

test('Opciones por tipo (Inertia): catálogo → atributos → valores', async ({ page }, testInfo) => {
  const watcher = watchForErrors(page, BASE);

  const res = await page.goto('/classifications/catalog', { waitUntil: 'networkidle' });
  expect(res?.status()).toBeLessThan(400);
  expect(page.url()).not.toContain('/admin/login');
  await expect(page.locator('h1')).toContainText('Opciones por tipo');

  const manage = page.locator('a:has-text("Gestionar")').first();
  const showHref = await manage.getAttribute('href');
  expect(showHref).toMatch(/\/classifications\/catalog\/\d+/);

  await page.goto(showHref!, { waitUntil: 'networkidle' });
  await expect(page.locator('h1')).toContainText('Atributos');
  await expect(page.getByRole('button', { name: /Añadir atributo/i })).toBeVisible();

  watcher.assert(testInfo);
});
