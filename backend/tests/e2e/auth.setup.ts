import { test as setup, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const STORAGE_STATE = 'tests/e2e/.auth/admin.json';

/**
 * Login asistido: por el reCAPTCHA de producción, un humano debe completar el
 * formulario y resolver el captcha en la ventana que se abre. En cuanto la app
 * navega al panel, guardamos la sesión para reutilizarla en el resto de specs.
 *
 * Correr una sola vez:  npm run test:e2e:setup
 */
setup('login admin (manual, resuelve captcha)', async ({ page }) => {
  setup.setTimeout(300_000); // 5 min para que el usuario ingrese y resuelva el captcha

  await page.goto('/admin/login');

  console.log('\n>>> Ingresá tus credenciales de admin y resolvé el reCAPTCHA en la ventana.');
  console.log('>>> La sesión se guardará automáticamente al entrar al panel.\n');

  // Esperamos a que el login termine: salimos de /admin/login hacia el panel.
  await page.waitForURL((url) => !url.pathname.includes('/admin/login'), {
    timeout: 290_000,
  });

  // Confirmamos que hay sesión real (cookie de Laravel presente).
  const cookies = await page.context().cookies();
  expect(cookies.some((c) => /session|laravel/i.test(c.name))).toBeTruthy();

  fs.mkdirSync(path.dirname(STORAGE_STATE), { recursive: true });
  await page.context().storageState({ path: STORAGE_STATE });
  console.log(`\n>>> Sesión guardada en ${STORAGE_STATE}\n`);
});
