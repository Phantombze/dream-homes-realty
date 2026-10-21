/**
 * One-off: converts the old MySQL export (scripts/legacy-export.json) into the
 * structured content in src/data/*.json.
 *
 * The old database stored presentation, not data: "650 / Monthly" as the
 * price, "3 Bedroom" as a stat string, the land size only inside the badge.
 * Editors need numbers and choices, so each value is parsed into a real field
 * here, and the report at the end flags anything that could not be.
 *
 * src/data/ is then both the fallback the site renders if Payload is down and
 * what the CMS seed loads, so the two are identical by construction.
 *
 *   node scripts/convert-legacy.mjs
 */
import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const here = path.dirname(fileURLToPath(import.meta.url))
const legacy = JSON.parse(await fs.readFile(path.resolve(here, '../../scripts/legacy-export.json'), 'utf8'))
const images = JSON.parse(await fs.readFile(path.resolve(here, 'image-map.json'), 'utf8'))
const DATA = path.resolve(here, '../src/data')
const s = legacy.settings

const img = (src, alt) => ({ src, alt })
const media = (legacyPath, alt) => img(images.media[legacyPath], alt)
const report = []

// --- Taxonomy --------------------------------------------------------------

// The old category names, tidied ("Appartments" was a typo in the data).
const categoryMeta = {
  1: { name: 'Land', slug: 'land', image: '/uploads/media/4.webp', description: 'Lots and acreage across Belize, from highway frontage to quiet country land.' },
  2: { name: 'Apartments', slug: 'apartments', image: '/uploads/media/Ladyville-Laundry-Mat-Front-Appartments-2.jpg', description: 'Ready-to-move-in rentals with utilities included.' },
  3: { name: 'Homes', slug: 'homes', image: '/uploads/media/Scene_1.jpg', description: 'Newly built family homes in established neighbourhoods.' },
}
const categories = Object.entries(categoryMeta).map(([id, c], i) => ({
  legacyId: Number(id),
  name: c.name,
  slug: c.slug,
  description: c.description,
  image: media(c.image, `${c.name} for sale in Belize`),
  order: i + 1,
}))

const locations = [
  { name: 'Ladyville', slug: 'ladyville', region: 'Belize District', order: 1 },
  { name: 'San Pablo', slug: 'san-pablo', region: 'Orange Walk District', order: 2 },
]
const locationFor = (text) => (/san pablo/i.test(text) ? 'san-pablo' : 'ladyville')

const agents = [
  {
    name: s['agent.name'] || 'Dean Sosa',
    slug: 'dean-sosa',
    role: 'Dream Homes Realty Agent',
    photo: media('/uploads/media/Dean_PFP.jpg', 'Dean Sosa, Dream Homes Realty agent'),
    phone: s['agent.phone'] || '+501 672-8009',
    whatsapp: s['contact.whatsapp_number'] || '5016728009',
    email: s['agent.email'] || '',
  },
]

// --- Listings --------------------------------------------------------------

const amenityIcon = (label, oldIcon) => {
  const l = label.toLowerCase()
  if (l.includes('electric')) return 'electricity'
  if (l.includes('water')) return 'water'
  if (l.includes('butane') || l.includes('gas')) return 'gas'
  if (l.includes('parking')) return 'parking'
  if (l.includes('tile')) return 'tiled'
  if (l.includes('burglar') || l.includes('secur')) return 'security'
  if (l.includes('fence')) return 'fenced'
  if (l.includes('air')) return 'ac'
  return oldIcon === 'utilities' ? 'electricity' : 'check'
}

const parseStats = (stats) => {
  const out = { bedrooms: null, bathrooms: null, kitchens: null, highlights: [] }
  for (const stat of stats) {
    const m = stat.match(/^(\d+)\s+(bed|bath|kitchen)/i)
    if (!m) {
      out.highlights.push(stat)
      continue
    }
    const key = { bed: 'bedrooms', bath: 'bathrooms', kitchen: 'kitchens' }[m[2].toLowerCase()]
    out[key] = Number(m[1])
  }
  return out
}

const catByListing = Object.fromEntries(legacy.listing_categories.map((r) => [r.listing_id, r.category_id]))
const imagesByListing = {}
for (const row of legacy.listing_images) (imagesByListing[row.listing_id] ??= []).push(row.file_path)

// The old listings had no description at all — the detail page printed the
// same generic paragraph for every property. These are written strictly from
// each listing's own data so nothing is invented; editors can expand them.
const summaries = {
  'ladyville-apartments': 'Single-room apartment for rent in Ladyville, with electricity, water and butane included in the rent and parking on site.',
  'ladyville-appartments-double-room': 'Two-bedroom apartment for rent in Ladyville, with electricity, water and butane included in the rent and parking on site.',
  'tubal-land-for-sale': 'One acre of land on the highway in Tubal, Ladyville, a prime location for a home or business.',
  'san-pablo': '17.4 acres of land with highway frontage in San Pablo, Orange Walk.',
  'vista-home-for-sale': 'Newly built three-bedroom, two-bathroom home in Vista Del Mar, Ladyville. Tiled throughout, air-conditioned rooms, burglar-barred windows, fully fenced, with parking.',
  'new-vista-home-for-sale': 'Newly built three-bedroom, two-bathroom home in Vista Del Mar, Ladyville.',
}

const listings = legacy.listings.map((l, i) => {
  const rent = /month/i.test(l.price)
  const price = Number(String(l.price).replace(/[^\d.]/g, ''))
  const stats = parseStats(JSON.parse(l.stats_json || '[]'))
  const acres = (l.badge.match(/([\d.]+)\s*acre/i) || [])[1]
  const gallery = (imagesByListing[l.id] ?? []).map((p, n) => img(images.listings[p], `${l.title}, photo ${n + 1}`))
  const amenities = JSON.parse(l.amenities_json || '[]').map((a) => ({ label: a.label.trim(), icon: amenityIcon(a.label, a.icon) }))
  const category = categoryMeta[catByListing[l.id]]?.slug ?? null
  const coords = l.map_query.trim()

  if (!price) report.push(`${l.slug}: could not parse price "${l.price}"`)
  if (!category) report.push(`${l.slug}: no category`)
  if (!gallery.length) report.push(`${l.slug}: no photos`)

  return {
    legacyId: l.id,
    title: l.title.replace(/\s*-\s*/, ' – ').replace('Apartments– ', 'Apartments – '),
    slug: l.slug,
    status: 'available',
    deal: rent ? 'rent' : 'sale',
    price,
    category,
    location: locationFor(l.location),
    badge: l.badge ? { text: l.badge, style: l.badge_style === 'gold' ? 'gold' : 'navy' } : null,
    featured: Boolean(Number(l.featured)),
    summary: summaries[l.slug] ?? '',
    bedrooms: stats.bedrooms,
    bathrooms: stats.bathrooms,
    kitchens: stats.kitchens,
    floorArea: null,
    landSize: acres ? { value: Number(acres), unit: 'acres' } : null,
    highlights: stats.highlights,
    amenities,
    gallery,
    videoUrl: l.video_url || '',
    mapLocation: coords,
    agent: 'dean-sosa',
    order: 100 - i,
    publishedAt: null,
  }
})

// --- Singletons ------------------------------------------------------------

const embedSrc = (s['contact.maps_query'].match(/src="([^"]+)"/) || [])[1] ?? ''

const settings = {
  companyName: 'Dream Homes Realty',
  tagline: 'We can find you the perfect property and help you locate the home of your dreams.',
  logo: img('/logo.png', 'Dream Homes Realty'),
  phones: [
    { label: 'Main', display: s['contact.phone_primary'] },
    { label: 'Office', display: s['contact.phone_secondary'] },
  ],
  email: s['contact.email_main'],
  whatsapp: s['contact.whatsapp_number'],
  address: `${s['contact.address']}\nLadyville, Belize`,
  mapEmbedUrl: embedSrc,
  mapLink: 'https://maps.app.goo.gl/7LZmcT6MHBVzDbaH8',
  officeHours: [
    { days: 'Monday – Friday', hours: '9:00am – 5:00pm' },
    { days: 'Saturday', hours: 'By appointment' },
    { days: 'Sunday', hours: 'Closed' },
  ],
  socials: [
    { platform: 'instagram', url: s['social.instagram_url'] },
    { platform: 'facebook', url: s['social.facebook_url'] },
  ].filter((x) => x.url),
  footerColumns: [
    {
      title: 'General Info',
      links: [
        { label: 'About Us', href: '/about/' },
        { label: 'All Properties', href: '/listings/' },
        { label: 'Contact Us', href: '/contact/' },
      ],
    },
    {
      title: 'Quick Links',
      links: [
        { label: 'Homes for Sale', href: '/listings/?category=homes' },
        { label: 'Apartments for Rent', href: '/listings/?category=apartments' },
        { label: 'Land for Sale', href: '/listings/?category=land' },
      ],
    },
  ],
  copyright: 'Dream Homes Realty. All rights reserved.',
  seo: {
    title: 'Dream Homes Realty | Belize Real Estate',
    description: 'Homes, land and rentals in Belize from Dream Homes Realty in Ladyville. Browse current listings and speak directly with our agent.',
    image: media('https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1920&q=80', 'Modern living room'),
  },
}

const homePage = {
  heroSlides: [
    {
      eyebrow: 'Exclusively Belize',
      title: 'Find Your Piece of',
      highlight: 'Paradise.',
      text: 'Discover the most prestigious properties in Belize, from private island estates to modern beachfront villas.',
      image: media('https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1920&q=80', 'Bright open-plan living room'),
    },
    {
      eyebrow: 'Luxury Living',
      title: 'Modern Design Meets',
      highlight: 'Island Soul.',
      text: 'Experience architectural excellence designed to blend seamlessly with the natural beauty of the Caribbean coast.',
      image: media('https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1920&q=80', 'Modern villa with a pool'),
    },
    {
      eyebrow: 'Investment Opportunity',
      title: 'Secure Your',
      highlight: 'Future Today.',
      text: "Belize is one of the fastest-growing markets in the region. Don't wait — contact us today.",
      image: media('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1920&q=80', 'Contemporary home at dusk'),
    },
  ],
  slideSeconds: 6,
  searchPlaceholder: 'Search by city, district, or keyword…',
  showHeroSocials: true,
  listings: { eyebrow: 'Fresh on the Market', title: 'Exclusive Listings', show: 'latest', limit: 8 },
  collections: { eyebrow: 'Find Your Match', title: 'Curated Collections', linkLabel: 'View All Listings' },
  quote: {
    eyebrow: 'Why buy from us?',
    text: 'Every property we sell is personally owned, carefully selected, and priced with long-term value in mind.',
    agent: 'dean-sosa',
    background: media('https://images.unsplash.com/photo-1544550581-5f7ceaf7f992?auto=format&fit=crop&w=1920&q=80', 'Caribbean coastline'),
  },
  cta: {
    eyebrow: 'Concierge Service',
    title: 'Selling or Buying?',
    text: 'Experience the gold standard in Belize real estate.',
    primary: { label: 'Speak to Agent', action: 'call', href: '' },
    secondary: { label: 'WhatsApp Us', action: 'whatsapp', href: '' },
  },
  seo: { title: '', description: '' },
}

const listingsPage = {
  eyebrow: 'Belize Real Estate',
  title: 'Properties for Sale & Rent',
  intro: 'Homes, land and apartments across Belize, each personally selected by our team. Filter by type or search by place.',
  searchPlaceholder: 'Search by location, price, or keyword…',
  featuredTitle: 'Featured Properties',
  emptyText: 'No properties match your search. Try another area or clear the filters.',
  seo: { title: 'Listings', description: 'Browse homes, land and apartments for sale and rent in Belize.' },
}

const aboutPage = {
  hero: {
    eyebrow: 'Welcome to Dream Homes',
    title: 'Who We Are',
    text: 'Dream Homes Realty helps clients find, buy, sell, and rent quality properties throughout Belize with a process that is clear, practical, and fully supported.',
  },
  pillars: [
    { icon: 'target', title: 'Our Mission', text: 'From lots to move-in-ready homes, we help clients identify the best-fit property with integrity, local expertise, and personalized guidance.' },
    { icon: 'flag', title: 'Our Goals', text: 'To provide opportunities for buyers to own their own private homes in Belize and start a new life in paradise.' },
    { icon: 'heart', title: 'Our Values', text: 'We help buyers and investors evaluate options with both present needs and future goals in mind. Smart decisions for long-term value.' },
  ],
  community: {
    eyebrow: 'Beyond Real Estate',
    title: 'Our Heart for the Community',
    rows: [
      {
        eyebrow: 'Giving Back',
        title: "Supporting the Ladyville Children's Home",
        text: "Our work is not only about property. We are committed to giving back, including ongoing support for the Ladyville Children's Home and community-centered initiatives.\n\nBy choosing Dream Homes Realty, clients partner with a team that believes business success should create positive social impact in Belize.",
        image: media('/uploads/media/LCH.jpg', "Dream Homes Realty supporting the Ladyville Children's Home"),
      },
      {
        eyebrow: 'Youth Empowerment',
        title: 'Sports Programs & Opportunities',
        text: "Children's sport tournaments for U11 and U13 teams promoting youths in sports and helping them develop team skills and confidence.\n\nAlong with summer camps for children to engage in sport activities, we are committed to supporting the next generation of Belizeans with opportunities for growth and success.",
        image: media('/uploads/media/Youths_Support_2.jpeg', 'Youth sports programme supported by Dream Homes Realty'),
      },
    ],
  },
  services: {
    eyebrow: 'What We Do',
    title: 'Services and Community Commitment',
    items: [
      { icon: 'home', label: 'Property Sales' },
      { icon: 'key', label: 'Home Rentals' },
      { icon: 'heart', label: 'Community Support' },
      { icon: 'trophy', label: 'Youth Engagement' },
    ],
  },
  cta: {
    eyebrow: 'Next Steps',
    title: 'Let’s Find the Right Property in Belize',
    text: 'Browse available listings or speak with our team for personalized guidance.',
    primary: { label: 'View Listings', action: 'link', href: '/listings/' },
    secondary: { label: 'Contact Us', action: 'link', href: '/contact/' },
  },
  seo: { title: 'About Us', description: 'Dream Homes Realty helps clients buy, sell and rent property across Belize, and gives back to the Ladyville community.' },
}

const contactPage = {
  hero: { eyebrow: 'Get in Touch', title: 'Contact Dream Homes Realty', text: 'Tell us what you’re looking for and our team will respond with clear next steps.' },
  eyebrow: 'Contact',
  title: 'We’re Here to Help',
  formTitle: 'Send a Message',
  formIntro: 'Share the basics and we’ll route you to the right agent.',
  topics: ['Buying', 'Selling', 'Renting', 'Investing', 'General'],
  successMessage: 'Thank you — your message has been sent. We’ll be in touch shortly.',
  seo: { title: 'Contact', description: 'Call, WhatsApp or message Dream Homes Realty in Ladyville, Belize.' },
}

const write = (name, data) => fs.writeFile(path.join(DATA, `${name}.json`), JSON.stringify(data, null, 2) + '\n')
await fs.mkdir(DATA, { recursive: true })
await Promise.all([
  write('realty-settings', settings),
  write('realty-home', homePage),
  write('realty-listings-page', listingsPage),
  write('realty-about', aboutPage),
  write('realty-contact', contactPage),
  write('listings', listings),
  write('listing-categories', categories),
  write('locations', locations),
  write('agents', agents),
])

console.log('\n  slug                               deal  price     cat         beds baths photos amenities')
for (const l of listings) {
  console.log(
    `  ${l.slug.padEnd(34)} ${l.deal.padEnd(5)} ${String(l.price).padEnd(9)} ${String(l.category).padEnd(11)} ${String(l.bedrooms ?? '-').padEnd(4)} ${String(l.bathrooms ?? '-').padEnd(5)} ${String(l.gallery.length).padEnd(6)} ${l.amenities.length}`,
  )
}
console.log(report.length ? `\n  MISSING:\n  ${report.join('\n  ')}` : '\n  nothing missing')
