import { Link } from '@tanstack/react-router'
import type { Person } from '#/lib/people'
import { CategoryBadges } from './CategoryBadges'
import { CountryLabel } from './CountryLabel'
import { PersonAvatar } from './PersonAvatar'
import { PersonDates } from './PersonDates'
import { WebsiteLink } from './WebsiteLink'

export function PersonRow({ person }: { person: Person }) {
  return (
    <div className="relative flex gap-4 p-4 transition-colors hover:bg-accent sm:p-5">
      <PersonAvatar person={person} className="size-14 text-lg" />
      <div className="min-w-0 flex-1 space-y-2">
        <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5">
          <h2 className="min-w-0 text-lg font-bold break-words">
            {/* Links can't nest, so this one stretches over the row and the website sits above it. */}
            <Link to="/people/$id" params={{ id: String(person.id) }} className="after:absolute after:inset-0">
              {person.display_name}
            </Link>
          </h2>
          <CategoryBadges person={person} />
        </div>
        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
          {person.country && <CountryLabel country={person.country} />}
          <PersonDates person={person} />
          <WebsiteLink website={person.website} className="relative z-10" />
        </div>
        {person.bio && <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">{person.bio}</p>}
      </div>
    </div>
  )
}
