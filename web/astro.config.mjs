// @ts-check
import { defineConfig } from 'astro/config'
import node from '@astrojs/node'
import icon from 'astro-icon'

export default defineConfig({
  site: process.env.PUBLIC_SITE_URL || 'https://www.dreamhomes-realty.com',

  // Rendered on request, not at build time: an edit saved in Payload is on the
  // site the moment the save completes. Pages read Payload over the loopback
  // through a short in-memory cache (src/lib/cms.ts), and the CMS clears that
  // cache on every save, so this costs a few milliseconds, not a rebuild.
  output: 'server',
  adapter: node({ mode: 'standalone' }),

  // Pages are linked with a trailing slash (/about/) and their canonical URL
  // always has one (BaseLayout), but 'always' would also demand it of files
  // such as /media/photo.webp and /sitemap.xml, so slashes are not enforced.
  trailingSlash: 'ignore',

  // Astro rejects POSTs whose Origin is not this site. The only POST endpoint
  // here is /api/revalidate/, called server-to-server by the CMS (no Origin
  // header) and guarded by a shared secret instead. The contact form posts to
  // the separate contact service, not to this app. Re-enable if a browser-
  // facing POST route is ever added.
  security: { checkOrigin: false },

  integrations: [
    icon({
      include: {
        lucide: ['*'],
        'fa6-brands': ['facebook-f', 'instagram', 'whatsapp', 'linkedin-in', 'youtube', 'tiktok', 'x-twitter'],
      },
    }),
  ],

  prefetch: { prefetchAll: true, defaultStrategy: 'hover' },

  server: { port: 4330, host: '127.0.0.1' },
})
