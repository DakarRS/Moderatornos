import type { Person } from '#/lib/people'
import { cn } from '#/lib/utils'

type Props = { country: NonNullable<Person['country']>; className?: string }

export function CountryLabel({ country, className }: Props) {
  return (
    <p className={cn('flex items-center gap-1.5 text-muted-foreground', className)}>
      <span className={`fi fi-${country.code.toLowerCase()} shrink-0 rounded-[2px]`} aria-hidden />
      <span className="truncate">{country.name}</span>
    </p>
  )
}
