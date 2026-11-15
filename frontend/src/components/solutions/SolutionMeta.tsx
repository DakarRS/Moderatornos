import { Link } from '@tanstack/react-router'
import { BadgeCheck } from 'lucide-react'
import { Badge } from '#/components/ui/badge'
import { formatDate } from '#/lib/people'
import { EDITION_LABELS } from '#/lib/solutions'
import type { Solution } from '#/lib/solutions'

export function SolutionMeta({ solution, linkTags = false }: { solution: Solution; linkTags?: boolean }) {
  return (
    <div className="flex flex-wrap items-center gap-2 text-xs">
      <Badge variant="secondary">{EDITION_LABELS[solution.edition]}</Badge>
      {solution.versions && <Badge variant="outline">{solution.versions}</Badge>}
      {solution.tags.map((tag) =>
        linkTags ? (
          <Badge key={tag.slug} variant="outline" asChild>
            <Link to="/solutions" search={{ tag: tag.slug }}>
              #{tag.name}
            </Link>
          </Badge>
        ) : (
          <Badge key={tag.slug} variant="outline">
            #{tag.name}
          </Badge>
        ),
      )}
      {solution.last_verified_on && (
        <span className="inline-flex items-center gap-1 text-muted-foreground">
          <BadgeCheck className="size-3.5" aria-hidden /> Verified {formatDate(solution.last_verified_on)}
        </span>
      )}
    </div>
  )
}
