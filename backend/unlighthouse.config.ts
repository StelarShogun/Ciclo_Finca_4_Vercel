import { defineUnlighthouseConfig } from 'unlighthouse/config'
import {
  puppeteerOptionsFor,
  sharedChrome,
  sharedLighthouseOptions,
  site,
} from './unlighthouse.shared'

/**
 * Guest / public storefront (CF4-133 focus).
 * Explicit urls disables crawler and sitemap per URL discovery guide.
 * @see https://unlighthouse.dev/guide/guides/url-discovery
 */
export default defineUnlighthouseConfig({
  site,
  debug: process.env.UNLIGHTHOUSE_DEBUG === '1',
  cache: true,
  outputPath: './lighthouse/',
  urls: [
    '/',
    '/catalog',
    process.env.UNLIGHTHOUSE_PRODUCT_PATH ?? '/product/1',
  ],
  scanner: {
    maxRoutes: 20,
    device: 'mobile',
  },
  lighthouseOptions: {
    ...sharedLighthouseOptions,
  },
  chrome: {
    ...sharedChrome,
  },
  puppeteerOptions: puppeteerOptionsFor('guest'),
})
