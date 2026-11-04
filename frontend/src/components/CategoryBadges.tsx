import { Badge } from '#/components/ui/badge'
import type { Person } from '#/lib/people'

export function CategoryBadges({ person }: { person: Person }) {
  return (
    <div className="flex flex-wrap gap-1.5">
      {person.categories.map((category) => (
        <Badge key={category.slug} variant="secondary">
          {category.name}
        </Badge>
      ))}
    </div>
  )
}
