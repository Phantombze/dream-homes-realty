/**
 * Content layer. Every page reads Payload through here, on the server, per
 * request — nothing in this file ships to the browser.
 *
 * Three properties matter:
 *
 *  1. Live. Pages are rendered on request, so a save in Payload is visible on
 *     the next page view. Responses are cached in memory for CACHE_TTL_MS so a
 *     burst of visitors costs one Payload query, and the CMS calls
 *     /api/revalidate/ on every save to clear that cache immediately.
 *
 *  2. Never down because the CMS is. If Payload is unreachable the last good
 *     response is served (however old), and if there has never been one, the
 *     committed copy in src/data/ is. A Payload restart is invisible to
 *     visitors.
 *
 *  3. Scoped to one tenant. The CMS is shared with other brands, so every
 *     query is filtered to PAYLOAD_TENANT.
 */
const API_BASE = (import.meta.env.PAYLOAD_API_URL || process.env.PAYLOAD_API_URL || 'http://localhost:3000').replace(/\/$/, '')
const TENANT = import.meta.env.PAYLOAD_TENANT || process.env.PAYLOAD_TENANT || 'dreamhomes'
const TIMEOUT_MS = Number(process.env.PAYLOAD_TIMEOUT_MS || 4000)
const CACHE_TTL_MS = Number(process.env.CMS_CACHE_TTL_MS || 60_000)

type Entry = { value: unknown; at: number }
const cache = new Map<string, Entry>()
const warned = new Set<string>()

function warnOnce(key: string, message: string) {
  if (warned.has(key)) return
  warned.add(key)
  console.warn(`[cms] ${message}`)
}

/** Clears every cached response. Called by /api/revalidate/ when Payload saves. */
export function purgeCache() {
  cache.clear()
  tenantId = undefined
  warned.clear()
}

async function fetchJson(url: string): Promise<any | null> {
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), TIMEOUT_MS)
  try {
    const res = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } })
    if (!res.ok) {
      warnOnce(url, `${url} returned ${res.status}`)
      return null
    }
    return await res.json()
  } catch (err) {
    warnOnce(url, `could not reach ${url} (${err instanceof Error ? err.message : err})`)
    return null
  } finally {
    clearTimeout(timer)
  }
}

/**
 * Serves `key` from cache while fresh; otherwise runs `load`. A load that
 * returns null (CMS down, empty) keeps serving the previous value rather than
 * dropping to fallback content that may be older than what visitors just saw.
 */
async function cached<T>(key: string, load: () => Promise<T | null>, fallback: () => Promise<T>): Promise<T> {
  const hit = cache.get(key)
  if (hit && Date.now() - hit.at < CACHE_TTL_MS) return hit.value as T

  const live = await load()
  if (live !== null) {
    cache.set(key, { value: live, at: Date.now() })
    return live
  }
  if (hit) return hit.value as T
  return fallback()
}

/**
 * Tenant slug -> id. Payload cannot filter on `tenant.slug` across the
 * relationship ("The following path cannot be queried: slug"), so the slug is
 * exchanged for an id and every query filters on `where[tenant][equals]=<id>`.
 */
let tenantId: Promise<string | null> | undefined
function resolveTenantId(): Promise<string | null> {
  tenantId ??= fetchJson(`${API_BASE}/api/tenants?limit=1&depth=0&where[slug][equals]=${encodeURIComponent(TENANT)}`).then((res) => {
    const id = res?.docs?.[0]?.id
    if (!id) {
      warnOnce('tenant', `no tenant "${TENANT}" in Payload — showing the committed fallback content.`)
      tenantId = undefined // try again on the next request rather than forever
      return null
    }
    return String(id)
  })
  return tenantId
}

const fallbacks = import.meta.glob('../data/*.json', { eager: true, import: 'default' }) as Record<string, unknown>
function loadFallback<T>(slug: string): T {
  const data = fallbacks[`../data/${slug}.json`]
  if (data === undefined) throw new Error(`No CMS data for "${slug}" and no src/data/${slug}.json to fall back to.`)
  return structuredClone(data) as T
}

const META_KEYS = new Set(['id', 'tenant', 'createdAt', 'updatedAt', 'globalType', '_status'])

/** One-per-site content: settings, the home page, the about page. */
export function getSingle<T = any>(slug: string): Promise<T> {
  return cached(
    `single:${slug}`,
    async () => {
      const id = await resolveTenantId()
      if (!id) return null
      const res = await fetchJson(`${API_BASE}/api/${slug}?depth=2&limit=1&where[tenant][equals]=${id}`)
      const doc = res?.docs?.[0]
      // A document that exists but has never been filled in comes back with
      // only its metadata; show the seed content rather than an empty page.
      const usable = doc && Object.keys(doc).some((k) => !META_KEYS.has(k))
      if (res && !usable) warnOnce(`empty:${slug}`, `"${slug}" has no content yet in Payload — showing the committed fallback.`)
      return usable ? (doc as T) : null
    },
    async () => loadFallback<T>(slug),
  )
}

/** Applies a Payload-style sort (`order`, `-price`) to fallback rows. */
function sortDocs<T>(docs: T[], sort: string): T[] {
  const desc = sort.startsWith('-')
  const key = desc ? sort.slice(1) : sort
  return [...docs].sort((a: any, b: any) => {
    const av = a?.[key]
    const bv = b?.[key]
    if (av === bv) return 0
    if (av == null) return 1
    if (bv == null) return -1
    const cmp = typeof av === 'number' && typeof bv === 'number' ? av - bv : String(av).localeCompare(String(bv))
    return desc ? -cmp : cmp
  })
}

/** Every document in a collection for this tenant. */
export function getCollection<T = any>(slug: string, opts: { sort?: string; limit?: number; where?: Record<string, string> } = {}): Promise<T[]> {
  const sort = opts.sort ?? 'order'
  return cached(
    `collection:${slug}:${JSON.stringify(opts)}`,
    async () => {
      const id = await resolveTenantId()
      if (!id) return null
      const params = new URLSearchParams({ depth: '2', limit: String(opts.limit ?? 200), sort })
      params.set('where[tenant][equals]', id)
      for (const [k, v] of Object.entries(opts.where ?? {})) params.set(k, v)
      const res = await fetchJson(`${API_BASE}/api/${slug}?${params}`)
      // An empty result is a real answer (every listing sold and removed), so
      // it is shown as empty. Only an unreachable CMS falls back.
      if (!res || !Array.isArray(res.docs)) return null
      return res.docs as T[]
    },
    async () => sortDocs(loadFallback<T[]>(slug), sort),
  )
}

/** Where Payload's API lives, for the media proxy. */
export const cmsOrigin = API_BASE
