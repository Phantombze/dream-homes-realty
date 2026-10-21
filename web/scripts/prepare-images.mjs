/**
 * One-off: turns the old site's uploads into web-ready images in
 * public/images/seed/, which serve two jobs:
 *
 *  1. The fallback content (src/data/*.json) points at them, so the site still
 *     renders every photo if Payload is unreachable.
 *  2. The CMS seed uploads them into Payload's Media collection.
 *
 * The three hero photos and the quote-band photo were hot-linked from Unsplash
 * on the old site. They are downloaded once here so the site no longer depends
 * on a third-party CDN for its most visible images.
 *
 *   LEGACY_DIR=../../dream-homes-legacy/site node scripts/prepare-images.mjs
 */
import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import sharp from 'sharp'

const here = path.dirname(fileURLToPath(import.meta.url))
const LEGACY = process.env.LEGACY_DIR
if (!LEGACY) throw new Error('Set LEGACY_DIR to the old site folder (the one holding uploads/).')
const OUT = path.resolve(here, '../public/images/seed')
const exportJson = JSON.parse(await fs.readFile(path.resolve(here, '../../scripts/legacy-export.json'), 'utf8'))

await fs.mkdir(OUT, { recursive: true })

const MAX_WIDTH = 1920

async function write(input, name) {
  const out = path.join(OUT, `${name}.webp`)
  const info = await sharp(input).rotate().resize({ width: MAX_WIDTH, withoutEnlargement: true }).webp({ quality: 78 }).toFile(out)
  console.log(`  ${name}.webp  ${info.width}x${info.height}  ${(info.size / 1024).toFixed(0)} KB`)
  return `/images/seed/${name}.webp`
}

const fromLegacy = (p) => path.join(LEGACY, p.replace(/^\/+/, ''))

// Listing photos: <slug>-01.webp, <slug>-02.webp … in gallery order.
const slugById = Object.fromEntries(exportJson.listings.map((l) => [l.id, l.slug]))
const counter = {}
const listingMap = {}
for (const img of exportJson.listing_images) {
  const slug = slugById[img.listing_id]
  counter[slug] = (counter[slug] ?? 0) + 1
  const name = `${slug}-${String(counter[slug]).padStart(2, '0')}`
  listingMap[img.file_path] = await write(fromLegacy(img.file_path), name)
}

// Category covers, About photos, the agent portrait.
const named = {
  '/uploads/media/4.webp': 'category-land',
  '/uploads/media/Ladyville-Laundry-Mat-Front-Appartments-2.jpg': 'category-apartments',
  '/uploads/media/Scene_1.jpg': 'category-homes',
  '/uploads/media/LCH.jpg': 'about-community',
  '/uploads/media/Youths_Support_2.jpeg': 'about-youth',
  '/uploads/media/Youths_Support.jpeg': 'about-youth-2',
  '/uploads/media/Dean_PFP.jpg': 'agent-dean-sosa',
}
const mediaMap = {}
for (const [src, name] of Object.entries(named)) mediaMap[src] = await write(fromLegacy(src), name)

// Previously hot-linked from Unsplash (free licence, no attribution required).
const unsplash = {
  'hero-1': 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1920&q=80',
  'hero-2': 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1920&q=80',
  'hero-3': 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1920&q=80',
  'quote-band': 'https://images.unsplash.com/photo-1544550581-5f7ceaf7f992?auto=format&fit=crop&w=1920&q=80',
}
for (const [name, url] of Object.entries(unsplash)) {
  const res = await fetch(url)
  if (!res.ok) throw new Error(`${url} returned ${res.status}`)
  mediaMap[url] = await write(Buffer.from(await res.arrayBuffer()), name)
}

// Brand files go to public/ as-is.
await fs.copyFile(fromLegacy('assets/img/logo.png'), path.resolve(here, '../public/logo.png'))
await fs.copyFile(fromLegacy('assets/img/favicon.ico'), path.resolve(here, '../public/favicon.ico'))
await sharp(fromLegacy('assets/img/logo.png')).resize(180, 180, { fit: 'contain', background: '#0a1f33' }).png().toFile(path.resolve(here, '../public/apple-touch-icon.png'))

await fs.writeFile(
  path.resolve(here, 'image-map.json'),
  JSON.stringify({ listings: listingMap, media: mediaMap }, null, 2) + '\n',
)
console.log('wrote scripts/image-map.json')
