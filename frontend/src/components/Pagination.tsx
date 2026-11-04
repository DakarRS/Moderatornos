import { Link } from '@tanstack/react-router'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { Button } from '#/components/ui/button'

export function Pagination({ page, lastPage }: { page: number; lastPage: number }) {
  if (lastPage <= 1) return null

  return (
    <nav aria-label="Pagination" className="mt-8 flex items-center justify-between gap-4">
      {page > 1 ? (
        <Button variant="outline" asChild>
          <Link to="/" search={(prev) => ({ ...prev, page: page === 2 ? undefined : page - 1 })}>
            <ChevronLeft aria-hidden /> Previous
          </Link>
        </Button>
      ) : (
        <Button variant="outline" disabled>
          <ChevronLeft aria-hidden /> Previous
        </Button>
      )}
      <span className="text-sm text-muted-foreground">
        Page {page} of {lastPage}
      </span>
      {page < lastPage ? (
        <Button variant="outline" asChild>
          <Link to="/" search={(prev) => ({ ...prev, page: page + 1 })}>
            Next <ChevronRight aria-hidden />
          </Link>
        </Button>
      ) : (
        <Button variant="outline" disabled>
          Next <ChevronRight aria-hidden />
        </Button>
      )}
    </nav>
  )
}
