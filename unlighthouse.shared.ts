/**
 * Shared Unlighthouse settings for Ciclo Finca 4 local scans.
 * @see https://unlighthouse.dev/guide/guides/config
 */

export const site = process.env.UNLIGHTHOUSE_SITE ?? 'http://localhost:8080'

/** Laravel default: Str::slug(APP_NAME)-session → ciclo-finca-session */
export const sessionCookieName =
  process.env.UNLIGHTHOUSE_SESSION_COOKIE ?? 'ciclo-finca-session'

export const sharedLighthouseOptions = {
  onlyCategories: ['performance'] as const,
  disableStorageReset: true,
  skipAboutBlank: true,
}

const chromePath = process.env.UNLIGHTHOUSE_CHROME_PATH?.trim()

/** Puppeteer launch options (no userDataDir — cluster workers cannot share one profile). */
export function puppeteerOptionsFor(_profile: string) {
  return {
    ...(chromePath ? { executablePath: chromePath } : {}),
  }
}

/** @see https://unlighthouse.dev/guide/guides/chrome-dependency */
export const sharedChrome = {
  useSystem: false,
  useDownloadFallback: !chromePath,
}

export type SessionCookie = {
  name: string
  value: string
  domain: string
  path: string
}

/** Decode cookie values copied from DevTools (may be URL-encoded). */
export function decodeCookieValue(value: string): string {
  const trimmed = value.trim()
  if (!trimmed.includes('%')) {
    return trimmed
  }

  try {
    return decodeURIComponent(trimmed)
  } catch {
    return trimmed
  }
}

function parseOneCookie(pair: string, defaultName: string): SessionCookie | null {
  const trimmed = pair.trim()
  if (!trimmed) {
    return null
  }

  if (trimmed.includes('=')) {
    const eq = trimmed.indexOf('=')
    const name = trimmed.slice(0, eq).trim()
    const value = decodeCookieValue(trimmed.slice(eq + 1))

    return { name, value, domain: 'localhost', path: '/' }
  }

  return {
    name: defaultName,
    value: decodeCookieValue(trimmed),
    domain: 'localhost',
    path: '/',
  }
}

/** Parse UNLIGHTHOUSE_*_COOKIE as "name=value", raw value, or "a=1; b=2". */
export function parseSessionCookie(
  raw: string | undefined,
  defaultName: string = sessionCookieName,
): SessionCookie[] | false {
  const trimmed = raw?.trim()
  if (!trimmed) {
    return false
  }

  const parts = trimmed.includes(';')
    ? trimmed.split(';').map((p) => p.trim()).filter(Boolean)
    : [trimmed]

  const cookies = parts
    .map((part) => parseOneCookie(part, defaultName))
    .filter((c): c is SessionCookie => c !== null)

  return cookies.length ? cookies : false
}

/** Session + optional XSRF-TOKEN for Laravel admin/client scans. */
export function parseAuthCookies(
  sessionRaw: string | undefined,
  xsrfRaw?: string | undefined,
  defaultSessionName: string = sessionCookieName,
): SessionCookie[] | false {
  const cookies: SessionCookie[] = []
  const session = parseSessionCookie(sessionRaw, defaultSessionName)
  if (session) {
    cookies.push(...session)
  }

  const xsrfTrimmed = xsrfRaw?.trim()
  if (xsrfTrimmed) {
    if (xsrfTrimmed.includes('=')) {
      const parsed = parseOneCookie(xsrfTrimmed, 'XSRF-TOKEN')
      if (parsed) {
        cookies.push(parsed)
      }
    } else {
      cookies.push({
        name: 'XSRF-TOKEN',
        value: decodeCookieValue(xsrfTrimmed),
        domain: 'localhost',
        path: '/',
      })
    }
  }

  return cookies.length ? cookies : false
}
