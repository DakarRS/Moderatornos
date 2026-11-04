import { CalendarDays } from 'lucide-react'
import { formatDate } from '#/lib/people'
import type { Person } from '#/lib/people'
import { cn } from '#/lib/utils'

type Props = { person: Person; className?: string }

export function PersonDates({ person, className }: Props) {
  if (!person.joined_on && !person.left_on) return null

  return (
    <p className={cn('flex items-center gap-1.5 text-muted-foreground', className)}>
      <CalendarDays className="size-3.5 shrink-0" aria-hidden />
      <span className="flex flex-wrap gap-x-3">
        {person.joined_on && <span>Joined {formatDate(person.joined_on)}</span>}
        {person.left_on && <span>Left {formatDate(person.left_on)}</span>}
      </span>
    </p>
  )
}
