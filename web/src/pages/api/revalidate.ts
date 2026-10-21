/**
 * Payload calls this after every save (see the realty hooks in the CMS) so an
 * edit shows on the next page view instead of after the cache expires.
 *
 * It only clears this process's in-memory cache, so the worst a caller who
 * guessed the secret could do is make the next few page views a little slower.
 */
import type { APIRoute } from 'astro'
import { timingSafeEqual } from 'node:crypto'
import { purgeCache } from '../../lib/cms'

const SECRET = process.env.REVALIDATE_SECRET || import.meta.env.REVALIDATE_SECRET || ''

const matches = (given: string) => {
  if (!SECRET || given.length !== SECRET.length) return false
  return timingSafeEqual(Buffer.from(given), Buffer.from(SECRET))
}

export const POST: APIRoute = async ({ request }) => {
  if (!matches(request.headers.get('x-revalidate-secret') ?? '')) {
    return new Response(JSON.stringify({ ok: false }), { status: 401, headers: { 'Content-Type': 'application/json' } })
  }
  purgeCache()
  return new Response(JSON.stringify({ ok: true, at: new Date().toISOString() }), { headers: { 'Content-Type': 'application/json' } })
}
