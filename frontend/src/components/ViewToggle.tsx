import { LayoutGrid, List } from 'lucide-react'
import { ToggleGroup, ToggleGroupItem } from '#/components/ui/toggle-group'

export type DirectoryView = 'grid' | 'list'

type Props = { value: DirectoryView; onChange: (view: DirectoryView) => void }

export function ViewToggle({ value, onChange }: Props) {
  return (
    <div className="flex items-center gap-2">
      <span id="display-as" className="text-sm text-muted-foreground">
        Display as
      </span>
      <ToggleGroup
        type="single"
        variant="outline"
        aria-labelledby="display-as"
        value={value}
        // Radix sends an empty value when the active item is clicked again; keep the current view.
        onValueChange={(next) => next && onChange(next as DirectoryView)}
      >
        <ToggleGroupItem value="grid" className="cursor-pointer">
          <LayoutGrid aria-hidden /> Grid
        </ToggleGroupItem>
        <ToggleGroupItem value="list" className="cursor-pointer">
          <List aria-hidden /> List
        </ToggleGroupItem>
      </ToggleGroup>
    </div>
  )
}
