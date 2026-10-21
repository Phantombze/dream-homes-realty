/**
 * The handful of queries every page needs, assembled once per request.
 */
import { getCollection, getSingle } from './cms'
import { toAgent, toCategory, toImage, toListing, toLocation, type Agent, type Category, type Image, type Listing, type Location } from './content'

export type Social = { platform: string; url: string }

export type Settings = {
  companyName: string
  tagline: string
  logo: Image | null
  phones: { label: string; display: string }[]
  email: string
  whatsapp: string
  address: string
  mapEmbedUrl: string
  mapLink: string
  officeHours: { days: string; hours: string }[]
  socials: Social[]
  footerColumns: { title: string; links: { label: string; href: string }[] }[]
  copyright: string
  seo: { title: string; description: string; image: Image | null }
}

export async function getSettings(): Promise<Settings> {
  const s = await getSingle('realty-settings')
  return {
    companyName: s.companyName ?? 'Dream Homes Realty',
    tagline: s.tagline ?? '',
    logo: toImage(s.logo, s.companyName),
    phones: (s.phones ?? []).filter((p: any) => p?.display),
    email: s.email ?? '',
    whatsapp: s.whatsapp ?? '',
    address: s.address ?? '',
    mapEmbedUrl: s.mapEmbedUrl ?? '',
    mapLink: s.mapLink ?? '',
    officeHours: s.officeHours ?? [],
    socials: (s.socials ?? []).filter((x: any) => x?.url),
    footerColumns: s.footerColumns ?? [],
    copyright: s.copyright ?? '',
    seo: { title: s.seo?.title ?? '', description: s.seo?.description ?? '', image: toImage(s.seo?.image) },
  }
}

export async function getTaxonomy(): Promise<{ categories: Category[]; locations: Location[]; agents: Agent[] }> {
  const [categories, locations, agents] = await Promise.all([
    getCollection('listing-categories'),
    getCollection('locations'),
    getCollection('agents', { sort: 'name' }),
  ])
  return {
    categories: categories.map(toCategory),
    locations: locations.map(toLocation),
    agents: agents.map(toAgent),
  }
}

/** Every published listing, newest first. */
export async function getListings(): Promise<Listing[]> {
  const [docs, taxonomy] = await Promise.all([getCollection('listings', { sort: '-createdAt' }), getTaxonomy()])
  return docs.map((d) => toListing(d, taxonomy))
}

export const SOCIAL_ICONS: Record<string, { icon: string; label: string }> = {
  instagram: { icon: 'fa6-brands:instagram', label: 'Instagram' },
  facebook: { icon: 'fa6-brands:facebook-f', label: 'Facebook' },
  whatsapp: { icon: 'fa6-brands:whatsapp', label: 'WhatsApp' },
  linkedin: { icon: 'fa6-brands:linkedin-in', label: 'LinkedIn' },
  youtube: { icon: 'fa6-brands:youtube', label: 'YouTube' },
  tiktok: { icon: 'fa6-brands:tiktok', label: 'TikTok' },
  x: { icon: 'fa6-brands:x-twitter', label: 'X' },
}

/** Amenity choices in the CMS → Lucide icons. */
export const AMENITY_ICONS: Record<string, string> = {
  electricity: 'lucide:zap',
  water: 'lucide:droplets',
  gas: 'lucide:flame',
  parking: 'lucide:car',
  tiled: 'lucide:grid-2x2',
  security: 'lucide:shield-check',
  fenced: 'lucide:fence',
  ac: 'lucide:snowflake',
  pool: 'lucide:waves',
  wifi: 'lucide:wifi',
  furnished: 'lucide:sofa',
  laundry: 'lucide:washing-machine',
  garden: 'lucide:trees',
  view: 'lucide:sunset',
  gated: 'lucide:door-closed',
  storage: 'lucide:warehouse',
  check: 'lucide:check',
}
