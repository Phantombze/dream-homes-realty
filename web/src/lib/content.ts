/**
 * Turns whatever the CMS (or the fallback JSON) returns into the shapes the
 * components render. Components never see a raw Payload document, so a schema
 * change is fixed here once rather than in every template.
 */

export type Image = {
  src: string
  alt: string
  width?: number
  height?: number
  /** Pre-generated sizes from Payload, ready for `srcset`. */
  srcset?: string
  /** Focal point as CSS `object-position`, when the editor set one. */
  position?: string
}

/**
 * Payload serves uploads from the CMS host, which is not public. The site
 * proxies them at /media/<file> (src/pages/media/[...file].ts), so a Payload
 * URL in any form is rewritten to that path.
 */
const mediaPath = (url: string): string => {
  const i = url.indexOf('/api/media/file/')
  return i === -1 ? url : `/media/${url.slice(i + '/api/media/file/'.length)}`
}

export function toImage(value: any, fallbackAlt = ''): Image | null {
  if (!value) return null
  if (typeof value === 'string') return { src: mediaPath(value), alt: fallbackAlt }
  const src = value.url ?? value.src
  if (!src) return null

  const sizes = value.sizes ? Object.values(value.sizes as Record<string, any>) : []
  const srcset = sizes
    .filter((s: any) => s?.url && s?.width)
    .sort((a: any, b: any) => a.width - b.width)
    .map((s: any) => `${mediaPath(s.url)} ${s.width}w`)
  if (srcset.length && value.width) srcset.push(`${mediaPath(src)} ${value.width}w`)

  const hasFocal = typeof value.focalX === 'number' && typeof value.focalY === 'number'
  return {
    src: mediaPath(src),
    alt: value.alt || fallbackAlt,
    width: value.width,
    height: value.height,
    srcset: srcset.length > 1 ? srcset.join(', ') : undefined,
    position: hasFocal ? `${value.focalX}% ${value.focalY}%` : undefined,
  }
}

/** A relationship arrives as a populated object from Payload, or a slug from the fallback. */
const refSlug = (value: any): string | null => (value == null ? null : typeof value === 'string' ? value : (value.slug ?? null))

// --- Money -----------------------------------------------------------------

const bzd = new Intl.NumberFormat('en-BZ', { maximumFractionDigits: 0 })

export const formatPrice = (amount: number | null | undefined) => (amount ? `BZ$${bzd.format(amount)}` : '')

// --- Contact links ---------------------------------------------------------

export const telHref = (display: string) => {
  const digits = display.replace(/[^\d+]/g, '')
  if (!digits) return ''
  return `tel:${digits.startsWith('+') ? digits : `+${digits}`}`
}

export const whatsappHref = (number: string, text?: string) => {
  const digits = (number || '').replace(/\D/g, '')
  if (!digits) return ''
  return `https://wa.me/${digits}${text ? `?text=${encodeURIComponent(text)}` : ''}`
}

// --- Taxonomy --------------------------------------------------------------

export type Category = { name: string; slug: string; description: string; image: Image | null; order: number }
export type Location = { name: string; slug: string; region: string; label: string }
export type Agent = { name: string; slug: string; role: string; photo: Image | null; phone: string; whatsapp: string; email: string }

export const toCategory = (c: any): Category => ({
  name: c.name,
  slug: c.slug,
  description: c.description ?? '',
  image: toImage(c.image, c.name),
  order: c.order ?? 0,
})

export const toLocation = (l: any): Location => ({
  name: l.name,
  slug: l.slug,
  region: l.region ?? '',
  label: [l.name, l.region].filter(Boolean).join(', '),
})

export const toAgent = (a: any): Agent => ({
  name: a.name,
  slug: a.slug,
  role: a.role ?? '',
  photo: toImage(a.photo, a.name),
  phone: a.phone ?? '',
  whatsapp: a.whatsapp ?? '',
  email: a.email ?? '',
})

// --- Listings --------------------------------------------------------------

export type ListingStatus = 'available' | 'under-offer' | 'sold' | 'rented'

export type Listing = {
  title: string
  slug: string
  href: string
  status: ListingStatus
  statusLabel: string
  deal: 'sale' | 'rent'
  dealLabel: string
  price: number
  priceLabel: string
  /** "/ month" for rentals. */
  priceSuffix: string
  category: Category | null
  location: Location | null
  badge: { text: string; style: 'gold' | 'navy' } | null
  featured: boolean
  summary: string
  descriptionHtml: string
  /** The few facts a card has room for, most useful first. */
  facts: { icon: string; value: string; label: string }[]
  highlights: string[]
  amenities: { label: string; icon: string }[]
  cover: Image | null
  gallery: Image[]
  videoId: string
  mapLocation: string
  agentSlug: string | null
  searchText: string
  order: number
  updatedAt: string | null
}

const STATUS_LABEL: Record<ListingStatus, string> = {
  available: 'Available',
  'under-offer': 'Under Offer',
  sold: 'Sold',
  rented: 'Rented',
}

/** youtube.com/watch?v=, youtu.be/, /embed/, /shorts/ → the video id. */
export function youtubeId(url: string): string {
  if (!url) return ''
  try {
    const u = new URL(url)
    if (u.hostname.includes('youtu.be')) return u.pathname.slice(1)
    if (!u.hostname.includes('youtube.com')) return ''
    return u.searchParams.get('v') ?? u.pathname.match(/\/(?:embed|shorts)\/([^/?]+)/)?.[1] ?? ''
  } catch {
    return ''
  }
}

const plural = (n: number, one: string, many = `${one}s`) => (n === 1 ? one : many)

export function toListing(doc: any, lookups: { categories: Category[]; locations: Location[] }): Listing {
  const catSlug = refSlug(doc.category)
  const locSlug = refSlug(doc.location)
  const category =
    (typeof doc.category === 'object' && doc.category ? toCategory(doc.category) : null) ??
    lookups.categories.find((c) => c.slug === catSlug) ??
    null
  const location =
    (typeof doc.location === 'object' && doc.location ? toLocation(doc.location) : null) ??
    lookups.locations.find((l) => l.slug === locSlug) ??
    null

  const gallery = (doc.gallery ?? [])
    .map((g: any, i: number) => toImage(g?.image ?? g, `${doc.title}, photo ${i + 1}`))
    .filter(Boolean) as Image[]

  const facts: Listing['facts'] = []
  if (doc.bedrooms) facts.push({ icon: 'bed-double', value: String(doc.bedrooms), label: plural(doc.bedrooms, 'Bed') })
  if (doc.bathrooms) facts.push({ icon: 'bath', value: String(doc.bathrooms), label: plural(doc.bathrooms, 'Bath') })
  if (doc.floorArea) facts.push({ icon: 'ruler', value: bzd.format(doc.floorArea), label: 'Sq Ft' })
  if (doc.landSize?.value) {
    const unit = doc.landSize.unit === 'sqft' ? 'Sq Ft Lot' : plural(doc.landSize.value, 'Acre')
    facts.push({ icon: 'land-plot', value: bzd.format(doc.landSize.value), label: unit })
  }
  if (doc.kitchens && facts.length < 3) facts.push({ icon: 'cooking-pot', value: String(doc.kitchens), label: plural(doc.kitchens, 'Kitchen') })

  const highlights: string[] = (doc.highlights ?? []).map((h: any) => (typeof h === 'string' ? h : h?.text)).filter(Boolean)
  const amenities = (doc.amenities ?? []).filter((a: any) => a?.label)
  const status: ListingStatus = doc.status ?? 'available'
  const deal = doc.deal === 'rent' ? 'rent' : 'sale'

  return {
    title: doc.title,
    slug: doc.slug,
    href: `/listings/${doc.slug}/`,
    status,
    statusLabel: STATUS_LABEL[status],
    deal,
    dealLabel: deal === 'rent' ? 'For Rent' : 'For Sale',
    price: doc.price ?? 0,
    priceLabel: doc.price ? formatPrice(doc.price) : 'Price on request',
    priceSuffix: deal === 'rent' && doc.price ? '/ month' : '',
    category,
    location,
    badge: doc.badge?.text ? { text: doc.badge.text, style: doc.badge.style === 'navy' ? 'navy' : 'gold' } : null,
    featured: Boolean(doc.featured),
    summary: doc.summary ?? '',
    descriptionHtml: doc.description_html ?? '',
    facts,
    highlights,
    amenities,
    cover: gallery[0] ?? null,
    gallery,
    videoId: youtubeId(doc.videoUrl ?? ''),
    mapLocation: (doc.mapLocation ?? '').trim(),
    agentSlug: refSlug(doc.agent),
    searchText: [doc.title, location?.label, category?.name, doc.summary, doc.badge?.text, ...highlights, ...amenities.map((a: any) => a.label), String(doc.price ?? '')]
      .filter(Boolean)
      .join(' ')
      .toLowerCase(),
    order: doc.order ?? 0,
    updatedAt: doc.updatedAt ?? null,
  }
}

/** Google Maps embed for coordinates (decimal or 17°32'56"N …) or a place name. */
export const mapEmbed = (q: string) => (q ? `https://www.google.com/maps?q=${encodeURIComponent(q)}&output=embed` : '')
export const mapLink = (q: string) => (q ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}` : '')
