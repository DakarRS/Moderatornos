import { createServerFn } from '@tanstack/react-start'

export type Person = {
  id: number
  display_name: string
  categories: { name: string; slug: string }[]
  country: { code: string; name: string } | null
  website: string | null
  bio: string | null
  avatar_url: string | null
  joined_on: string | null
  left_on: string | null
  updated_at: string
}

export type Category = { name: string; slug: string; people_count: number }

export type PeoplePage = {
  data: Person[]
  meta: { current_page: number; last_page: number; total: number }
}

export type Directory = {
  categories: Category[]
  totalPeople: number
  active: Category | null
  people: PeoplePage | null
}

export const MAX_SEARCH_LENGTH = 100
export const MAX_PAGE = 1_000_000

async function api(path: string) {
  const base = process.env.API_URL ?? 'http://localhost:8100'
  return fetch(`${base}/api${path}`, {
    headers: { Accept: 'application/json' },
    signal: AbortSignal.timeout(8000),
  })
}

async function json<T>(path: string): Promise<T> {
  const response = await api(path)
  if (!response.ok) throw new Error(`People API responded with ${response.status}`)
  return response.json()
}

export const loadDirectory = createServerFn({ method: 'GET' })
  .validator((input: { q?: string; page?: number; category?: string }) => input)
  .handler(async ({ data }): Promise<Directory> => {
    const search = data.q ? `&search=${encodeURIComponent(data.q)}` : ''
    const {
      data: categories,
      meta: { total_people: totalPeople },
    } = await json<{ data: Category[]; meta: { total_people: number } }>(`/categories${search && `?${search.slice(1)}`}`)

    // Slugs are editable, so an old link falls back to the first tab instead of failing.
    const active = categories.find((category) => category.slug === data.category) ?? categories[0] ?? null
    if (!active) return { categories, totalPeople, active, people: null }

    const people = await json<PeoplePage>(
      `/people?per_page=12&page=${data.page ?? 1}&category=${encodeURIComponent(active.slug)}${search}`,
    )
    return { categories, totalPeople, active, people }
  })

export const getPerson = createServerFn({ method: 'GET' })
  .validator((id: string) => id)
  .handler(async ({ data: id }): Promise<Person | null> => {
    const response = await api(`/people/${encodeURIComponent(id)}`)
    if (response.status === 404) return null
    if (!response.ok) throw new Error(`People API responded with ${response.status}`)
    return (await response.json()).data
  })

// These are calendar dates, so a local timezone would shift them a day and break hydration.
const dateFormat = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })

export function formatDate(date: string) {
  return dateFormat.format(new Date(`${date}T00:00:00Z`))
}

// React does not block javascript: hrefs, so never link anything but web URLs.
export function parseWebsite(website: string | null) {
  if (!website) return null
  try {
    const url = new URL(website)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return null
    return { href: url.href, label: (url.host + url.pathname).replace(/^www\./, '').replace(/\/$/, '') }
  } catch {
    return null
  }
}
