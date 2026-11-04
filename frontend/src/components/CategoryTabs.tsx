import { Tabs, TabsList, TabsTrigger } from '#/components/ui/tabs'
import type { Category } from '#/lib/people'
import type { ReactNode } from 'react'

type Props = {
  categories: Category[]
  activeSlug?: string
  onChange: (slug: string) => void
  actions: ReactNode
  children: ReactNode
}

export function CategoryTabs({ categories, activeSlug, onChange, actions, children }: Props) {
  return (
    <Tabs value={activeSlug} onValueChange={onChange} className="mt-8 gap-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        {/* Categories are admin-defined, so the row wraps rather than assuming they fit. */}
        <TabsList className="h-auto w-fit max-w-full flex-wrap justify-start">
          {categories.map((category) => (
            <TabsTrigger key={category.slug} value={category.slug} className="h-8 flex-none cursor-pointer px-3 text-base">
              {category.name}
              <span className="text-xs text-muted-foreground tabular-nums">{category.people_count}</span>
            </TabsTrigger>
          ))}
        </TabsList>
        {actions}
      </div>
      {children}
    </Tabs>
  )
}
