import { Link, createFileRoute, useRouterState } from '@tanstack/react-router'
import { X } from 'lucide-react'
import { useCallback } from 'react'
import { Pagination } from '#/components/Pagination'
import { SearchBox } from '#/components/SearchBox'
import { DirectorySkeleton, Message } from '#/components/Status'
import { LogMatcher } from '#/components/solutions/LogMatcher'
import { SolutionCard } from '#/components/solutions/SolutionCard'
import { Badge } from '#/components/ui/badge'
import { ToggleGroup, ToggleGroupItem } from '#/components/ui/toggle-group'
import { MAX_PAGE } from '#/lib/people'
import { seo } from '#/lib/seo'
import { MAX_SOLUTION_SEARCH_LENGTH, loadSolutions } from '#/lib/solutions'
import type { SolutionFilters } from '#/lib/solutions'

export const Route = createFileRoute('/solutions/')({
  // Clean hand-typed URLs here, since the API answers out-of-range values with a 422.
  validateSearch: (search: Record<string, unknown>): SolutionFilters => {
    const q = typeof search.q === 'string' || typeof search.q === 'number' ? String(search.q).trim() : ''
    const page = Number(search.page)
    return {
      q: q ? q.slice(0, MAX_SOLUTION_SEARCH_LENGTH) : undefined,
      page: Number.isInteger(page) && page > 1 && page <= MAX_PAGE ? page : undefined,
      tag: typeof search.tag === 'string' && search.tag ? search.tag.slice(0, 100) : undefined,
      edition: search.edition === 'java' || search.edition === 'bedrock' ? search.edition : undefined,
    }
  },
  loaderDeps: ({ search }) => search,
  loader: ({ deps }) => loadSolutions({ data: deps }),
  head: () => ({ meta: seo({ title: 'Solutions', description: 'Fixes for Aternos server problems the help center doesn’t cover.' }) }),
  pendingComponent: DirectorySkeleton,
  component: Solutions,
})

function Solutions() {
  const solutions = Route.useLoaderData()
  const { q, tag, edition } = Route.useSearch()
  const navigate = Route.useNavigate()
  const isLoading = useRouterState({ select: (state) => state.isLoading })
  const tagName = solutions.data.flatMap((solution) => solution.tags).find((t) => t.slug === tag)?.name ?? tag

  const setQuery = useCallback(
    (next: string) => navigate({ search: (prev) => ({ ...prev, q: next || undefined, page: undefined }), replace: true }),
    [navigate],
  )

  const setEdition = (next: string) =>
    navigate({
      search: (prev) => ({ ...prev, edition: next === 'java' || next === 'bedrock' ? next : undefined, page: undefined }),
    })

  return (
    <div className="grid gap-8 lg:grid-cols-[1fr_22rem]">
      <div className="min-w-0">
        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">Solutions</h1>
        <p className="mt-2 text-muted-foreground" aria-live="polite">
          {solutions.meta.total} {solutions.meta.total === 1 ? 'solution' : 'solutions'}
          {q ? ` matching “${q}”` : ' for problems the help center doesn’t cover'}
        </p>

        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
          <div className="flex-1">
            <SearchBox
              value={q ?? ''}
              onChange={setQuery}
              placeholder="Search errors, mods, symptoms…"
              label="Search solutions"
              maxLength={MAX_SOLUTION_SEARCH_LENGTH}
            />
          </div>
          <ToggleGroup type="single" variant="outline" aria-label="Edition" value={edition ?? 'all'} onValueChange={(value) => value && setEdition(value)}>
            <ToggleGroupItem value="all">All</ToggleGroupItem>
            <ToggleGroupItem value="java">Java</ToggleGroupItem>
            <ToggleGroupItem value="bedrock">Bedrock</ToggleGroupItem>
          </ToggleGroup>
        </div>

        {tag && (
          <Badge variant="secondary" className="mt-4" asChild>
            <Link to="." search={(prev) => ({ ...prev, tag: undefined, page: undefined })}>
              #{tagName} <X aria-label="Remove tag filter" />
            </Link>
          </Badge>
        )}

        <div className={`mt-6 space-y-4 ${isLoading ? 'opacity-60 transition-opacity' : 'transition-opacity'}`}>
          {solutions.data.length === 0 ? (
            <Message title="Nothing found">
              <p>{q || tag || edition ? 'Try other words or fewer filters.' : 'No solutions have been published yet.'}</p>
            </Message>
          ) : (
            solutions.data.map((solution) => <SolutionCard key={solution.slug} solution={solution} />)
          )}
          <Pagination page={solutions.meta.current_page} lastPage={solutions.meta.last_page} />
        </div>
      </div>

      <aside className="lg:sticky lg:top-20 lg:self-start">
        <LogMatcher />
      </aside>
    </div>
  )
}
