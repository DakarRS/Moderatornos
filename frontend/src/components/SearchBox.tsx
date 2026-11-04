import { Search } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Input } from '#/components/ui/input'
import { MAX_SEARCH_LENGTH } from '#/lib/people'

export function SearchBox({ value, onChange }: { value: string; onChange: (value: string) => void }) {
  const [draft, setDraft] = useState(value)
  const lastSent = useRef(value)

  // Follow back/forward navigation, but don't let a slow response overwrite newer typing.
  useEffect(() => {
    if (value !== lastSent.current) {
      lastSent.current = value
      setDraft(value)
    }
  }, [value])

  useEffect(() => {
    const next = draft.trim()
    if (next === lastSent.current) return
    const timer = setTimeout(() => {
      lastSent.current = next
      onChange(next)
    }, 250)
    return () => clearTimeout(timer)
  }, [draft, onChange])

  return (
    <div className="relative">
      <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
      <Input
        type="search"
        value={draft}
        maxLength={MAX_SEARCH_LENGTH}
        onChange={(event) => setDraft(event.target.value)}
        placeholder="Search by name or country"
        aria-label="Search people"
        className="h-10 bg-card pl-9"
      />
    </div>
  )
}
