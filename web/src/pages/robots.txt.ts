import type { APIRoute } from 'astro'

export const GET: APIRoute = ({ site }) => {
  const base = (site?.href ?? 'https://www.dreamhomes-realty.com').replace(/\/$/, '')
  return new Response(`User-agent: *\nAllow: /\nDisallow: /api/\n\nSitemap: ${base}/sitemap.xml\n`, {
    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
  })
}
