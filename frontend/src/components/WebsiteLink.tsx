import { Globe } from 'lucide-react'
import { parseWebsite } from '#/lib/people'
import { cn } from '#/lib/utils'

export function WebsiteLink({ website, className }: { website: string | null; className?: string }) {
  const parsed = parseWebsite(website)
  if (!parsed) return null

  return (
    <a
      href={parsed.href}
      target="_blank"
      rel="noreferrer"
      className={cn('inline-flex items-center gap-1.5 text-primary underline-offset-4 hover:underline', className)}
    >
      <Globe className="size-3.5 shrink-0" aria-hidden />
      <span className="break-all">{parsed.label}</span>
    </a>
  )
}
