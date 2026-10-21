# Dream Homes Realty

The website for Dream Homes Realty, Ladyville, Belize — <https://www.dreamhomes-realty.com>.

Astro, rendered on the server per request, with every page's content edited in the shared Payload CMS at
<https://cp.panelbz.cloud> (tenant `dreamhomes`). A save in Payload is on the site on the next page view.

The previous PHP + MySQL site is in this repository's history: the commit "Bring in the PHP site as it was deployed
on the server" is its last working version.

```
web/                Astro site (Node, @astrojs/node standalone)
  src/pages/        home, listings, listings/[slug], about, contact, 404, sitemap.xml, robots.txt,
                    media/[...file] (Payload image proxy), api/revalidate (cache clear), *.php.ts (old-URL redirects)
  src/components/   Header, Footer, HeroSlider, ListingCard, ListingSpotlight, ListingGallery, AgentCard, …
  src/lib/cms.ts    Payload reads: tenant-scoped, cached, falls back to src/data/ if the CMS is down
  src/lib/content.ts  CMS documents → the shapes components render (images, prices, listings)
  src/data/*.json   Fallback copy of all content; also what the CMS seed loads
  public/images/seed/ The old site's photos, optimised; seeded into Payload's Media
scripts/            One-off migration from the old MySQL database
```

## Run locally

```bash
cd web
npm install
npm run dev          # http://127.0.0.1:4330
```

With no CMS running the site renders from `src/data/` and logs one `[cms]` warning per resource. To work against a
local CMS, run the CMS (see the CMS repository's README) and set `PAYLOAD_API_URL` in `web/.env`
(copy `web/.env.example`).

## How content gets to the page

1. A visitor requests `/listings/`.
2. `src/lib/cms.ts` asks Payload (`127.0.0.1:3100` on the server) for this tenant's listings, unless it asked within
   the last 60 seconds.
3. Payload calls `POST /api/revalidate/` after every save, which empties that cache, so edits show immediately.
4. If Payload is down, the last good answer is served; if there has never been one, `src/data/` is.

Images uploaded in Payload are served at `/media/<file>` by a small proxy (the CMS host is not public), with
year-long immutable caching so Cloudflare serves them after the first view.

## Old URLs

`/index.php`, `/listings.php?category=N`, `/listing.php?slug=X`, `/about.php`, `/contact.php` and `/sitemap.php`
301 to their new addresses (`src/lib/legacy.ts`), so shared links and indexed pages keep working.

## Notes on the rebuild

- Category names were tidied: "Appartments" → "Apartments", "Land Properties" → "Land". Footer links on the old
  site pointed at the wrong categories (Homes → id 1, which was Land) and one that did not exist (id 4).
- Prices are numbers in BZD with a Sale / Rent choice; "/ month" is added for rentals. The old data stored text like
  `650 / Monthly`, and every listing page said "For Sale", including rentals.
- The old listing pages had no description, only a generic paragraph shared by every property. Each listing now has
  a short summary written strictly from its own data (rooms, amenities, land size), to be expanded in the CMS.
- Hero and quote-band photos were hot-linked from Unsplash; they are now local files / CMS media.
- The old settings table held an SMTP password in plain text. It was not carried over.
