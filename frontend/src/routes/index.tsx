import { createFileRoute, useRouterState } from '@tanstack/react-router'
import { useCallback } from 'react'
import { MAX_PAGE, MAX_SEARCH_LENGTH, loadDirectory } from '#/lib/people'
import { CategoryTabs } from '#/components/CategoryTabs'
import { Pagination } from '#/components/Pagination'
import { PeopleView } from '#/components/PeopleView'
import { SearchBox } from '#/components/SearchBox'
import { DirectorySkeleton, Message } from '#/components/Status'
import { TabsContent } from '#/components/ui/tabs'
import { ViewToggle } from '#/components/ViewToggle'
import type { DirectoryView } from '#/components/ViewToggle'

type DirectorySearch = { q?: string; page?: number; category?: string; view?: 'list' }

export const Route = createFileRoute('/')({
  // Clean hand-typed URLs here, since the API answers out-of-range values with a 422.
  validateSearch: (search: Record<string, unknown>): DirectorySearch => {
    const q = typeof search.q === 'string' || typeof search.q === 'number' ? String(search.q).trim() : ''
    const page = Number(search.page)
    return {
      q: q ? q.slice(0, MAX_SEARCH_LENGTH) : undefined,
      page: Number.isInteger(page) && page > 1 && page <= MAX_PAGE ? page : undefined,
      category: typeof search.category === 'string' && search.category ? search.category.slice(0, 100) : undefined,
      view: search.view === 'list' ? 'list' : undefined,
    }
  },
  // Leaves out view, since switching layout doesn't need new data.
  loaderDeps: ({ search: { q, page, category } }) => ({ q, page, category }),
  loader: ({ deps }) => loadDirectory({ data: deps }),
  pendingComponent: DirectorySkeleton,
  component: Directory,
})

function Directory() {
  const { categories, totalPeople, active, people } = Route.useLoaderData()
  const { q, view } = Route.useSearch()
  const navigate = Route.useNavigate()
  const isLoading = useRouterState({ select: (state) => state.isLoading })
  const firstSlug = categories[0]?.slug

  const setQuery = useCallback(
    (next: string) =>
      navigate({ search: (prev) => ({ ...prev, q: next || undefined, page: undefined }), replace: true }),
    [navigate],
  )

  // Switching tab drops the page, and the first tab keeps a clean URL.
  const setCategory = (slug: string) =>
    navigate({ search: (prev) => ({ q: prev.q, view: prev.view, category: slug === firstSlug ? undefined : slug }) })

  const setView = (next: DirectoryView) =>
    navigate({
      search: (prev) => ({ ...prev, view: next === 'list' ? 'list' : undefined }),
      replace: true,
      resetScroll: false,
    })

  return (
    <>
      <div className="grid items-end gap-6 md:grid-cols-[1fr_22rem]">
        <div>
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">People</h1>
          <p className="mt-2 text-muted-foreground" aria-live="polite">
            {totalPeople} {totalPeople === 1 ? 'person' : 'people'}
            {q ? ` matching “${q}”` : ' in the directory'}
          </p>
        </div>
        <SearchBox value={q ?? ''} onChange={setQuery} />
      </div>

      {active ? (
        <CategoryTabs categories={categories} activeSlug={active.slug} onChange={setCategory} actions={<ViewToggle value={view ?? 'grid'} onChange={setView} />}>
          <TabsContent value={active.slug} className={isLoading ? 'opacity-60 transition-opacity' : 'transition-opacity'}>
            {!people || people.data.length === 0 ? (
              <Message title="No one found">
                <p>{q ? 'Try a different name or country.' : 'There is no one here yet.'}</p>
              </Message>
            ) : (
              <PeopleView people={people.data} view={view ?? 'grid'} />
            )}
            {people && <Pagination page={people.meta.current_page} lastPage={people.meta.last_page} />}
          </TabsContent>
        </CategoryTabs>
      ) : (
        <Message title="No categories yet">
          <p>Add a category in the admin to get started.</p>
        </Message>
      )}
    </>
  )
}
