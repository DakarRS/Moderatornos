import type { Person } from '#/lib/people'
import { Avatar, AvatarFallback, AvatarImage } from '#/components/ui/avatar'
import { cn } from '#/lib/utils'

const TINTS = ['#4f46e5', '#0f766e', '#9333ea', '#b45309', '#2563eb', '#4d7c0f', '#be185d', '#475569']

// Array.from keeps emoji and other multi-unit characters in one piece.
function initials(name: string) {
  const words = name.trim().split(/\s+/)
  const letters = words.length > 1 ? words.slice(0, 2).map((word) => Array.from(word)[0]) : Array.from(words[0]).slice(0, 2)
  return letters.join('').toUpperCase()
}

export function PersonAvatar({ person, className }: { person: Person; className?: string }) {
  return (
    <Avatar className={cn('size-14 text-lg', className)}>
      {person.avatar_url && <AvatarImage src={person.avatar_url} alt="" className="object-cover" />}
      <AvatarFallback className="font-bold text-white" style={{ backgroundColor: TINTS[person.id % TINTS.length] }}>
        {initials(person.display_name)}
      </AvatarFallback>
    </Avatar>
  )
}
