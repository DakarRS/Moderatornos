import { HeadContent, Link, Outlet, Scripts, createRootRoute } from '@tanstack/react-router'
import { Users } from 'lucide-react'
import type { ReactNode } from 'react'
import { SITE_DESCRIPTION, seo } from '#/lib/seo'
import { SITE_NAME } from '#/lib/site'
import appCss from '../styles.css?url'

export const Route = createRootRoute({
  head: () => ({
    meta: [
      { charSet: 'utf-8' },
      { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ...seo({ description: SITE_DESCRIPTION }),
    ],
    links: [{ rel: 'stylesheet', href: appCss }],
  }),
  component: () => <Outlet />,
  shellComponent: RootDocument,
})

function RootDocument({ children }: { children: ReactNode }) {
  return (
    <html lang="en">
      <head>
        <HeadContent />
      </head>
      <body>
        <header className="sticky top-0 z-10 border-b bg-background/80 backdrop-blur">
          <div className="mx-auto flex h-14 max-w-6xl items-center px-4 sm:px-8">
            <Link to="/" className="flex items-center gap-2 font-bold">
              <span className="grid size-7 place-items-center rounded-md bg-primary text-primary-foreground">
                <Users className="size-4" aria-hidden />
              </span>
              {SITE_NAME}
            </Link>
          </div>
        </header>
        <main className="mx-auto max-w-6xl px-4 py-8 sm:px-8 sm:py-12">{children}</main>
        <Scripts />
      </body>
    </html>
  )
}
