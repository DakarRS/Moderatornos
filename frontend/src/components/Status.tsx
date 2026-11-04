import { Link, useRouter } from '@tanstack/react-router'
import type { ReactNode } from 'react'
import { Button } from '#/components/ui/button'
import { Card, CardContent } from '#/components/ui/card'
import { Skeleton } from '#/components/ui/skeleton'

export function Message({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="mx-auto max-w-md py-20 text-center">
      <h1 className="text-2xl font-bold">{title}</h1>
      <div className="mt-2 text-muted-foreground">{children}</div>
    </div>
  )
}

export function ErrorState() {
  const router = useRouter()
  return (
    <Message title="The directory is unavailable">
      <p>We couldn't load people just now.</p>
      <Button className="mt-6" onClick={() => router.invalidate()}>
        Try again
      </Button>
    </Message>
  )
}

export function NotFoundState() {
  return (
    <Message title="Nobody here">
      <p>That page or person doesn't exist.</p>
      <Button variant="outline" className="mt-6" asChild>
        <Link to="/">Back to the directory</Link>
      </Button>
    </Message>
  )
}

export function DirectorySkeleton() {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3" aria-hidden>
      {Array.from({ length: 6 }, (_, index) => (
        <Card key={index}>
          <CardContent className="flex items-center gap-4">
            <Skeleton className="size-14 rounded-full" />
            <div className="flex-1 space-y-2">
              <Skeleton className="h-5 w-2/3" />
              <Skeleton className="h-4 w-1/2" />
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}
