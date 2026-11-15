import { Link } from '@tanstack/react-router'
import { Card, CardContent } from '#/components/ui/card'
import { preview } from '#/lib/solutions'
import type { Solution } from '#/lib/solutions'
import { SolutionMeta } from './SolutionMeta'

export function SolutionCard({ solution }: { solution: Solution }) {
  return (
    <Link to="/solutions/$slug" params={{ slug: solution.slug }} className="group block rounded-xl">
      <Card className="transition-all group-hover:-translate-y-0.5 group-hover:border-primary/50 group-hover:shadow-md">
        <CardContent className="space-y-3">
          <h2 className="text-lg font-bold">{solution.title}</h2>
          <p className="line-clamp-2 text-sm text-muted-foreground">{preview(solution)}</p>
          <SolutionMeta solution={solution} />
        </CardContent>
      </Card>
    </Link>
  )
}
