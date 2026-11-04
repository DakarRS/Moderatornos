import { Card } from '#/components/ui/card'
import type { Person } from '#/lib/people'
import { PersonCard } from './PersonCard'
import { PersonRow } from './PersonRow'
import type { DirectoryView } from './ViewToggle'

export function PeopleView({ people, view }: { people: Person[]; view: DirectoryView }) {
  if (view === 'list') {
    return (
      <Card className="gap-0 divide-y overflow-hidden py-0">
        {people.map((person) => (
          <PersonRow key={person.id} person={person} />
        ))}
      </Card>
    )
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {people.map((person) => (
        <PersonCard key={person.id} person={person} />
      ))}
    </div>
  )
}
