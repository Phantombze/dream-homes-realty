/**
 * Serves images uploaded in Payload at /media/<file>.
 *
 * The CMS host (cp.panelbz.cloud) is not public, so the site fetches each file
 * from Payload over the loopback and hands it on. Payload stays in the path on
 * purpose: its access rules decide what may be read, rather than a web server
 * exposing the uploads folder to anyone who guesses a filename.
 *
 * Upload filenames never change once stored, so responses are cached for a
 * year by browsers and by Cloudflare — after the first view, an image never
 * touches this server again.
 */
import type { APIRoute } from 'astro'
import { cmsOrigin } from '../../lib/cms'

const SAFE_NAME = /^[\w.\-() ]+\.(?:jpe?g|png|webp|avif|gif|svg)$/i

export const GET: APIRoute = async ({ params }) => {
  const file = params.file ?? ''
  // One plain filename only: no slashes, no traversal, image types only.
  if (!SAFE_NAME.test(file)) return new Response('Not found', { status: 404 })

  let upstream: Response
  try {
    upstream = await fetch(`${cmsOrigin}/api/media/file/${encodeURIComponent(file)}`)
  } catch {
    return new Response('Image temporarily unavailable', { status: 503, headers: { 'Retry-After': '30' } })
  }
  if (!upstream.ok || !upstream.body) return new Response('Not found', { status: upstream.status === 404 ? 404 : 502 })

  const type = upstream.headers.get('content-type') ?? 'application/octet-stream'
  if (!type.startsWith('image/')) return new Response('Not found', { status: 404 })

  return new Response(upstream.body, {
    headers: {
      'Content-Type': type,
      'Cache-Control': 'public, max-age=31536000, immutable',
      'X-Content-Type-Options': 'nosniff',
      // SVG uploads can carry script; never let one run as a page.
      'Content-Security-Policy': "default-src 'none'; style-src 'unsafe-inline'; sandbox",
    },
  })
}
