/**
 * /sitemap.xml — the path the old site's robots.txt already pointed Google at.
 * Built per request from the live listings, so a new property is listed as
 * soon as it is published.
 */
import type { APIRoute } from 'astro'
import { getListings } from '../lib/site'

export const GET: APIRoute = async ({ site }) => {
  const base = (site?.href ?? 'https://www.dreamhomes-realty.com').replace(/\/$/, '')
  const listings = await getListings()
  const today = new Date().toISOString().slice(0, 10)

  const urls = [
    { loc: '/', lastmod: today, priority: '1.0' },
    { loc: '/listings/', lastmod: today, priority: '0.9' },
    ...listings.map((l) => ({ loc: l.href, lastmod: (l.updatedAt ?? today).slice(0, 10), priority: l.status === 'available' ? '0.8' : '0.4' })),
    { loc: '/about/', lastmod: today, priority: '0.6' },
    { loc: '/contact/', lastmod: today, priority: '0.6' },
  ]

  const body =
    '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
    urls.map((u) => `  <url><loc>${base}${u.loc}</loc><lastmod>${u.lastmod}</lastmod><priority>${u.priority}</priority></url>`).join('\n') +
    '\n</urlset>\n'

  return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8', 'Cache-Control': 'public, max-age=3600' } })
}
