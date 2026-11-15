import { Link, createFileRoute, notFound } from '@tanstack/react-router'
import { ArrowLeft } from 'lucide-react'
import { CopyReplyButton } from '#/components/solutions/CopyReplyButton'
import { SolutionMeta } from '#/components/solutions/SolutionMeta'
import { Button } from '#/components/ui/button'
import { Card, CardContent } from '#/components/ui/card'
import { seo } from '#/lib/seo'
import { getSolution, preview } from '#/lib/solutions'

export const Route = createFileRoute('/solutions/$slug')({
  loader: async ({ params }) => {
    const solution = await getSolution({ data: params.slug })
    if (!solution) throw notFound()
    return solution
  },
  head: ({ loaderData: solution }) => ({
    meta: solution ? seo({ title: solution.title, description: preview(solution) }) : [],
  }),
  component: SolutionPage,
})

function SolutionPage() {
  const solution = Route.useLoaderData()
  const sections = [
    { title: 'Symptoms', html: solution.html.symptoms },
    { title: 'Cause', html: solution.html.cause },
    { title: 'Fix', html: solution.html.fix },
  ]

  return (
    <article className="mx-auto max-w-3xl">
      <Button variant="ghost" size="sm" className="-ml-2" asChild>
        <Link to="/solutions">
          <ArrowLeft aria-hidden /> All solutions
        </Link>
      </Button>

      <Card className="mt-4">
        <CardContent className="space-y-6">
          <div className="space-y-3">
            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">{solution.title}</h1>
            <SolutionMeta solution={solution} linkTags />
          </div>

          {sections.map(
            ({ title, html }) =>
              html && (
                <section key={title}>
                  <h2 className="mb-2 text-sm font-bold tracking-wide text-muted-foreground uppercase">{title}</h2>
                  {/* Rendered by the API with raw HTML escaped and unsafe links removed. */}
                  <div className="markdown" dangerouslySetInnerHTML={{ __html: html }} />
                </section>
              ),
          )}

          <CopyReplyButton solution={solution} />
        </CardContent>
      </Card>
    </article>
  )
}
