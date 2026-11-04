import { Link } from '@tanstack/react-router'
import { Card, CardContent } from '#/components/ui/card'
import type { Person } from '#/lib/people'
import { CategoryBadges } from './CategoryBadges'
import { CountryLabel } from './CountryLabel'
import { PersonAvatar } from './PersonAvatar'
import { PersonDates } from './PersonDates'

export function PersonCard({ person }: { person: Person }) {
  return (
    <Link to="/people/$id" params={{ id: String(person.id) }} className="group block h-full rounded-xl">
      <Card className="h-full transition-all group-hover:-translate-y-0.5 group-hover:border-primary/50 group-hover:shadow-md">
        <CardContent className="flex h-full flex-col gap-4">
          <div className="flex items-center gap-4">
            <PersonAvatar person={person} />
            <div className="min-w-0">
              <h2 className="truncate text-lg font-bold">{person.display_name}</h2>
              {person.country && <CountryLabel country={person.country} className="mt-0.5 text-sm" />}
            </div>
          </div>
          {person.bio && <p className="line-clamp-2 text-sm text-muted-foreground">{person.bio}</p>}
          <div className="mt-auto space-y-3">
            <PersonDates person={person} className="text-xs" />
            <CategoryBadges person={person} />
          </div>
        </CardContent>
      </Card>
    </Link>
  )
}
