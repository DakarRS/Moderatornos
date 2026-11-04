import { Link, createFileRoute, notFound, useCanGoBack, useRouter } from '@tanstack/react-router'
import { ArrowLeft } from 'lucide-react'
import { CategoryBadges } from '#/components/CategoryBadges'
import { CountryLabel } from '#/components/CountryLabel'
import { PersonAvatar } from '#/components/PersonAvatar'
import { PersonDates } from '#/components/PersonDates'
import { WebsiteLink } from '#/components/WebsiteLink'
import { Button } from '#/components/ui/button'
import { Card, CardContent } from '#/components/ui/card'
import { getPerson } from '#/lib/people'
import { SITE_NAME } from '#/lib/site'

export const Route = createFileRoute('/people/$id')({
  loader: async ({ params }) => {
    const person = await getPerson({ data: params.id })
    if (!person) throw notFound()
    return person
  },
  head: ({ loaderData }) => ({
    meta: [{ title: loaderData ? `${loaderData.display_name} · ${SITE_NAME}` : SITE_NAME }],
  }),
  component: PersonPage,
})

function PersonPage() {
  const person = Route.useLoaderData()
  const router = useRouter()
  const canGoBack = useCanGoBack()

  return (
    <article className="mx-auto max-w-3xl">
      <Button variant="ghost" size="sm" className="-ml-2" asChild>
        {/* A person can be in several tabs, so going back is the only way to land on the right one. */}
        <Link
          to="/"
          onClick={(event) => {
            if (canGoBack) {
              event.preventDefault()
              router.history.back()
            }
          }}
        >
          <ArrowLeft aria-hidden /> Back to directory
        </Link>
      </Button>

      <Card className="mt-4">
        <CardContent>
          <div className="flex flex-col gap-6 sm:flex-row sm:items-center">
            <PersonAvatar person={person} className="size-28 text-4xl" />
            <div className="min-w-0">
              <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">{person.display_name}</h1>
              {person.country && <CountryLabel country={person.country} className="mt-2" />}
              <PersonDates person={person} className="mt-2 text-sm" />
              <div className="mt-3">
                <CategoryBadges person={person} />
              </div>
            </div>
          </div>

          {person.bio && <p className="mt-6 leading-relaxed whitespace-pre-line">{person.bio}</p>}

          <WebsiteLink website={person.website} className="mt-6 text-sm" />
        </CardContent>
      </Card>
    </article>
  )
}
