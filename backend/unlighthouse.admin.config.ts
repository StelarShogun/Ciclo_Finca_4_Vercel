import { defineUnlighthouseConfig } from 'unlighthouse/config'
import {
  parseAuthCookies,
  puppeteerOptionsFor,
  sharedChrome,
  sharedLighthouseOptions,
  site,
} from './unlighthouse.shared'

const adminCookies = parseAuthCookies(
  process.env.UNLIGHTHOUSE_ADMIN_COOKIE,
  process.env.UNLIGHTHOUSE_ADMIN_XSRF_TOKEN,
)

if (!adminCookies) {
  console.warn(
    '[unlighthouse:admin] UNLIGHTHOUSE_ADMIN_COOKIE is empty. Log in at /admin/login, copy the session cookie from DevTools, then re-run.',
  )
}

/**
 * Admin panel (session cookie required — reCAPTCHA blocks programmatic login).
 * @see https://unlighthouse.dev/guide/guides/authentication
 */
export default defineUnlighthouseConfig({
  site,
  debug: process.env.UNLIGHTHOUSE_DEBUG === '1',
  cache: true,
  outputPath: './lighthouse-admin/',
  cookies: adminCookies || false,
  urls: [
    '/dashboard',
    '/inventory',
    '/reports',
    '/orders',
    '/sales',
    '/reports/desempeno-ventas',
    '/reports/productos-vendidos',
  ],
  scanner: {
    maxRoutes: 60,
    device: 'desktop',
  },
  lighthouseOptions: {
    ...sharedLighthouseOptions,
    ...(adminCookies
      ? {
          extraHeaders: {
            Cookie: adminCookies.map((c) => `${c.name}=${c.value}`).join('; '),
          },
        }
      : {}),
  },
  chrome: {
    ...sharedChrome,
  },
  puppeteerOptions: puppeteerOptionsFor('admin'),
  // Parallel workers can navigate before cluster cookies are applied (dashboard → /admin/login).
  puppeteerClusterOptions: {
    maxConcurrency: 1,
  },
  hooks: adminCookies
    ? {
        // setCookie on about:blank often fails; warm up the origin first (see unlighthouse#182).
        async authenticate(page) {
          await page.goto(site, { waitUntil: 'domcontentloaded' })
          const browserCookies = await page.cookies()
          if (browserCookies.length) {
            await page.deleteCookie(
              ...browserCookies.map((c) => ({
                name: c.name,
                domain: c.domain,
                path: c.path,
              })),
            )
          }
          await page.setCookie(
            ...adminCookies.map((cookie) => ({
              ...cookie,
              domain: 'localhost',
              path: '/',
              httpOnly: true,
              secure: false,
              sameSite: 'Lax' as const,
            })),
          )
        },
      }
    : {},
})
