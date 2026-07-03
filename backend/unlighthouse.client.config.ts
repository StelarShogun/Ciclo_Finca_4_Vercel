import { defineUnlighthouseConfig } from 'unlighthouse/config'
import {
  parseAuthCookies,
  puppeteerOptionsFor,
  sharedChrome,
  sharedLighthouseOptions,
  site,
} from './unlighthouse.shared'

const clientCookies = parseAuthCookies(
  process.env.UNLIGHTHOUSE_CLIENT_COOKIE,
  process.env.UNLIGHTHOUSE_CLIENT_XSRF_TOKEN,
)

const productPath = process.env.UNLIGHTHOUSE_PRODUCT_PATH?.trim() || '/product/1'

const clientUrls = [
  '/',
  '/catalog',
  '/cart',
  '/profile',
  '/invoices',
  productPath,
]

if (!clientCookies) {
  console.warn(
    '[unlighthouse:client] UNLIGHTHOUSE_CLIENT_COOKIE is empty. Run artisan unlighthouse:client-cookie or set credentials in .env.unlighthouse.local.',
  )
}

/**
 * Authenticated client storefront (fixed routes — no catalog crawler).
 * @see https://unlighthouse.dev/guide/guides/url-discovery
 */
export default defineUnlighthouseConfig({
  site,
  debug: process.env.UNLIGHTHOUSE_DEBUG === '1',
  cache: true,
  outputPath: './lighthouse-client/',
  cookies: clientCookies || false,
  urls: clientUrls,
  scanner: {
    crawler: false,
    sitemap: false,
    robotsTxt: false,
    maxRoutes: clientUrls.length,
    device: 'mobile',
  },
  lighthouseOptions: {
    ...sharedLighthouseOptions,
    ...(clientCookies
      ? {
          extraHeaders: {
            Cookie: clientCookies.map((c) => `${c.name}=${c.value}`).join('; '),
          },
        }
      : {}),
  },
  chrome: {
    ...sharedChrome,
  },
  puppeteerOptions: puppeteerOptionsFor('client'),
  puppeteerClusterOptions: {
    maxConcurrency: 1,
  },
  hooks: clientCookies
    ? {
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
            ...clientCookies.map((cookie) => ({
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
    : {
        async authenticate(page) {
          const email = process.env.UNLIGHTHOUSE_CLIENT_EMAIL
          const password = process.env.UNLIGHTHOUSE_CLIENT_PASSWORD

          if (!email || !password) {
            throw new Error(
              'Set UNLIGHTHOUSE_CLIENT_COOKIE or UNLIGHTHOUSE_CLIENT_EMAIL + UNLIGHTHOUSE_CLIENT_PASSWORD in .env.unlighthouse.local.',
            )
          }

          await page.goto(`${site}/login`, { waitUntil: 'networkidle0' })
          await page.type('#login-email', email)
          await page.type('#login-password', password)

          await Promise.all([
            page.click('#public-login-form button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
          ])
        },
      },
})
