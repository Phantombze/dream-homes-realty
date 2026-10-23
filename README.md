# Dream Homes Realty

The website for Dream Homes Realty, Ladyville, Belize — <https://www.dreamhomes-realty.com>.

Astro, rendered on the server per request, with every page's content edited in the shared Payload CMS at
<https://cp.panelbz.cloud> (tenant `dreamhomes`). The CMS is its own repository,
[belize-sites-cms](https://github.com/Phantombze/belize-sites-cms) (private). A save in Payload is on the site on the next page view.

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

## Deploying

**Locked out of SSH?** SSH is allowed only from the operator's own IP
addresses. If your home or work IP changes, log in from the other one and
re-run `sudo bash /srv/src/server-ops/harden.sh`. If neither works, use
DigitalOcean's **Recovery Console** (droplet → Access), which does not go
through the firewall; the "Droplet Console" there uses SSH and is blocked
like any other address. Details: github.com/Phantombze/belize-server-ops.


On the server the site runs as `dreamhomes-web.service` (Node on `127.0.0.1:4330`, 220 MB cap) behind Caddy, which
also serves `/_astro/*` straight from disk and routes `/api/contact` to the shared contact service. Everything is in
`deploy/`:

| | |
|---|---|
| `deploy/bootstrap.sh` | Historical: the first deploy (2026-10-21), when the CMS lived inside the Medina's repository. Do not run it now. |
| `deploy/install.sh` | Install / re-run to repair. Expects the CMS at `/srv/src/cms`. Backs up the CMS database, adds the revalidate secret to the CMS, rebuilds the CMS, seeds Dream Homes (missing items only), builds and starts the site, installs the contact service, swaps Caddy from the holding page (rolls back if Caddy rejects it). |
| `deploy/update.sh` | Applies new code: pulls this repository from GitHub (it is public), runs the CMS's own update, loads new seed content, rebuilds the site only if it changed. |
| `deploy/set-admin-login.sh` | Resets the CMS superadmin's email and password (the CMS cannot send reset emails). |
| `deploy/set-contact-keys.sh` | Moved: forwards to the CMS repository's `ops/contact-forms/set-keys.sh`, which sets every site's own keys. |
| `deploy/dreamhomes-web.service` | systemd unit, sandboxed like `payload-cms`. |
| `deploy/dreamhomes.caddy` | The Caddy block that replaces the holding page. |

Updating the site's code: push to GitHub, then on the server

```bash
sudo bash /srv/src/dreamhomes/deploy/update.sh
```

To update the CMS at the same time, copy its bundle up first (see the
belize-sites-cms README); `update.sh` applies it.

### Contact form keys

Dream Homes has its own Turnstile widget (hostname `dreamhomes-realty.com`) and
its own Brevo API key. They are kept on the server only, never in git or the
CMS: `TURNSTILE_SECRET_KEY_DREAMHOMES` and `BREVO_API_KEY_DREAMHOMES` in
`/etc/site-contact/contact.env` (root, 600), and the public site key in
`/srv/deploy/turnstile-dreamhomes.env`, which a systemd drop-in hands to
`dreamhomes-web`. All sites' keys are set in one go with
`sudo bash /srv/src/cms/ops/contact-forms/set-keys.sh`. Enquiries go to the
inboxes listed for `dreamhomes` in `/opt/site-contact/sites.json`; the Brevo
account the key belongs to must have dreamhomes-realty.com verified as a
sending domain.

Content edits need nothing: they are live on save.

Editors: create a user in the CMS (role **User**, tenant **Dream Homes Realty**, role **Tenant editor**). They see only
the Dream Homes sections; Medina's are hidden from them.

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
