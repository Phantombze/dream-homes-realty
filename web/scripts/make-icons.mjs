/**
 * Builds the site icons from the house mark in public/logo.png: the white
 * house and gold swoosh, centred on a navy rounded square, so the icon reads
 * on light and dark browser tabs and on phone home screens.
 *
 *   node scripts/make-icons.mjs
 *
 * Writes public/favicon.ico (16, 32 and 48 px), favicon-32.png,
 * icon-192.png, icon-512.png and apple-touch-icon.png (180 px, square
 * corners: iOS rounds them itself).
 */
import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import sharp from 'sharp'

const here = path.dirname(fileURLToPath(import.meta.url))
const PUBLIC = path.resolve(here, '../public')
const NAVY = '#0f2b46'

// The mark is the first 208 px of the 1105×102 wordmark; the rest is the name.
const mark = await sharp(path.join(PUBLIC, 'logo.png')).extract({ left: 0, top: 0, width: 208, height: 102 }).png().toBuffer()

async function icon(size, { rounded = true } = {}) {
  const pad = Math.round(size * 0.08)
  const inner = await sharp(mark).resize(size - pad * 2, size - pad * 2, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).toBuffer()
  const r = rounded ? Math.round(size * 0.2) : 0
  const bg = Buffer.from(`<svg width="${size}" height="${size}"><rect width="${size}" height="${size}" rx="${r}" fill="${NAVY}"/></svg>`)
  return sharp(bg).composite([{ input: inner, gravity: 'center' }]).png().toBuffer()
}

/** An .ico holding PNG images (supported by every current browser). */
function ico(pngs) {
  const header = Buffer.alloc(6 + 16 * pngs.length)
  header.writeUInt16LE(0, 0)
  header.writeUInt16LE(1, 2)
  header.writeUInt16LE(pngs.length, 4)
  let offset = header.length
  pngs.forEach(({ size, data }, i) => {
    const e = 6 + i * 16
    header[e] = size >= 256 ? 0 : size
    header[e + 1] = size >= 256 ? 0 : size
    header.writeUInt16LE(1, e + 4)
    header.writeUInt16LE(32, e + 6)
    header.writeUInt32LE(data.length, e + 8)
    header.writeUInt32LE(offset, e + 12)
    offset += data.length
  })
  return Buffer.concat([header, ...pngs.map((p) => p.data)])
}

const sizes = [16, 32, 48]
const icoImages = await Promise.all(sizes.map(async (size) => ({ size, data: await icon(size) })))
await fs.writeFile(path.join(PUBLIC, 'favicon.ico'), ico(icoImages))
await fs.writeFile(path.join(PUBLIC, 'favicon-32.png'), await icon(32))
await fs.writeFile(path.join(PUBLIC, 'icon-192.png'), await icon(192))
await fs.writeFile(path.join(PUBLIC, 'icon-512.png'), await icon(512))
await fs.writeFile(path.join(PUBLIC, 'apple-touch-icon.png'), await icon(180, { rounded: false }))
console.log('icons written to public/')
