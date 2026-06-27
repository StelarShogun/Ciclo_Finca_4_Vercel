// Lanza Brave con una COPIA del perfil del usuario (sesión admin ya iniciada),
// confirma que la sesión es válida y la exporta a storageState para que la suite
// Playwright la reutilice. Brave descifra su propia sesión (password-store=basic).
import { chromium } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.E2E_BASE_URL || 'https://ciclo-finca-4-vercel.vercel.app';
const USER_DATA_DIR = process.env.BRAVE_PROFILE_COPY || '/tmp/brave-e2e-profile';
const BRAVE_BIN = process.env.BRAVE_BIN || '/opt/brave-origin-bin/brave';
const OUT = 'tests/e2e/.auth/admin.json';

const ctx = await chromium.launchPersistentContext(USER_DATA_DIR, {
  executablePath: BRAVE_BIN,
  headless: true,
  args: ['--password-store=basic', '--no-first-run', '--no-default-browser-check'],
});

try {
  const page = ctx.pages()[0] || (await ctx.newPage());
  const res = await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 45_000 });
  const status = res?.status();
  const url = page.url();
  console.log(`GET /dashboard -> ${status} (url: ${url})`);

  if (url.includes('/admin/login')) {
    throw new Error('La sesión copiada NO está autenticada (redirigió a /admin/login). ¿Brave tiene la sesión admin abierta?');
  }

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  await ctx.storageState({ path: OUT });
  console.log(`OK: sesión admin exportada a ${OUT}`);
} finally {
  await ctx.close();
}
