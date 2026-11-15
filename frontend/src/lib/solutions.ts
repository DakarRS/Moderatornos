import { createServerFn } from '@tanstack/react-start'
import { api, json, jsonOrNull } from '#/lib/api'
import { summarize } from '#/lib/seo'

export type Edition = 'java' | 'bedrock' | 'both'

export type Solution = {
  slug: string
  title: string
  symptoms: string | null
  cause: string | null
  fix: string
  // Rendered by the API with raw HTML escaped, so it's safe to insert.
  html: { symptoms: string | null; cause: string | null; fix: string }
  edition: Edition
  versions: string | null
  tags: { name: string; slug: string }[]
  last_verified_on: string | null
  updated_at: string
}

export type SolutionsPage = {
  data: Solution[]
  meta: { current_page: number; last_page: number; total: number }
}

export type SolutionFilters = { q?: string; page?: number; tag?: string; edition?: 'java' | 'bedrock' }

export const MAX_LOG_LINK_LENGTH = 200
export const MAX_SOLUTION_SEARCH_LENGTH = 200

export const EDITION_LABELS: Record<Edition, string> = { java: 'Java', bedrock: 'Bedrock', both: 'Java & Bedrock' }

export const loadSolutions = createServerFn({ method: 'GET' })
  .validator((input: SolutionFilters) => input)
  .handler(async ({ data }): Promise<SolutionsPage> => {
    const params = new URLSearchParams({ page: String(data.page ?? 1) })
    if (data.q) params.set('search', data.q)
    if (data.tag) params.set('tag', data.tag)
    if (data.edition) params.set('edition', data.edition)

    // A tag renamed since the link was shared shouldn't break the whole page.
    const response = await api(`/solutions?${params}`)
    if (response.status === 422 && params.has('tag')) {
      params.delete('tag')
      return json<SolutionsPage>(`/solutions?${params}`)
    }
    if (!response.ok) throw new Error(`API responded with ${response.status} for /solutions`)
    return response.json()
  })

export const getSolution = createServerFn({ method: 'GET' })
  .validator((slug: string) => slug)
  .handler(async ({ data: slug }): Promise<Solution | null> => {
    return (await jsonOrNull<{ data: Solution }>(`/solutions/${encodeURIComponent(slug)}`))?.data ?? null
  })

export type LogMatch = { ok: true; matches: Solution[] } | { ok: false; message: string }

export const matchLog = createServerFn({ method: 'POST' })
  .validator((url: string) => url.slice(0, MAX_LOG_LINK_LENGTH))
  .handler(async ({ data: url }): Promise<LogMatch> => {
    const response = await api('/solutions/match', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url }),
    })
    if (response.ok) return { ok: true, matches: (await response.json()).data }
    // The API words these for players, e.g. a wrong link or a log that's gone.
    if (response.status === 422) return { ok: false, message: (await response.json()).errors?.url?.[0] ?? 'That link didn’t work.' }
    if (response.status === 502) return { ok: false, message: (await response.json()).message }
    throw new Error(`API responded with ${response.status} for /solutions/match`)
  })

// Previews and link embeds show plain text, so drop the markdown punctuation.
export function preview(solution: Solution) {
  return summarize(
    (solution.symptoms ?? solution.fix)
      .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')
      .replace(/^\s*(#+|>|[-*+]|\d+\.)\s+/gm, '')
      .replace(/[*_`~]/g, ''),
  )
}

// Discord caps messages at 2000 characters, so leave room for the title and link.
const MAX_REPLY_FIX = 1500

export function discordReply(solution: Solution, url: string) {
  const fix = solution.fix.trim()
  const body = fix.length > MAX_REPLY_FIX ? `${fix.slice(0, MAX_REPLY_FIX).trimEnd()}…` : fix
  // Angle brackets stop Discord from adding a large link preview under the reply.
  return `**${solution.title}**\n${body}\n\nMore: <${url}>`
}
