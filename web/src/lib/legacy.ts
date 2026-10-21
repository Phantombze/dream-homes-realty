/**
 * Permanent redirects from the old PHP site's URLs, so links already shared
 * on WhatsApp and Facebook, and pages Google has indexed, land on the right
 * page instead of a 404. Each old URL has a one-line endpoint in src/pages
 * (index.php.ts, listing.php.ts …) that calls this.
 */
import type { APIRoute } from 'astro'

// The old site addressed categories by database id.
const LEGACY_CATEGORY: Record<string, string> = { '1': 'land', '2': 'apartments', '3': 'homes' }

export function legacyTarget(url: URL): string {
  switch (url.pathname) {
    case '/listing.php': {
      const slug = url.searchParams.get('slug')
      return slug ? `/listings/${encodeURIComponent(slug)}/` : '/listings/'
    }
    case '/listings.php': {
      const params = new URLSearchParams()
      const category = LEGACY_CATEGORY[url.searchParams.get('category') ?? '']
      if (category) params.set('category', category)
      const q = url.searchParams.get('q')
      if (q) params.set('q', q)
      const qs = params.toString()
      return `/listings/${qs ? `?${qs}` : ''}`
    }
    case '/about.php':
      return '/about/'
    case '/contact.php':
      return '/contact/'
    case '/sitemap.php':
      return '/sitemap.xml'
    default:
      return '/'
  }
}

export const legacyRedirect: APIRoute = ({ url, redirect }) => redirect(legacyTarget(url), 301)
