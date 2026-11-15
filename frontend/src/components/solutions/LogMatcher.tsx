import { useServerFn } from '@tanstack/react-start'
import { FileSearch, LoaderCircle } from 'lucide-react'
import { useState } from 'react'
import { Button } from '#/components/ui/button'
import { Card, CardContent } from '#/components/ui/card'
import { Input } from '#/components/ui/input'
import { MAX_LOG_LINK_LENGTH, matchLog } from '#/lib/solutions'
import type { LogMatch } from '#/lib/solutions'
import { SolutionCard } from './SolutionCard'

type Result = { status: 'idle' } | { status: 'loading' } | { status: 'done'; result: LogMatch }

const UNEXPECTED: LogMatch = { ok: false, message: 'Something went wrong. Try again.' }

export function LogMatcher() {
  const [link, setLink] = useState('')
  const [state, setState] = useState<Result>({ status: 'idle' })
  const match = useServerFn(matchLog)

  const submit = async (event: React.FormEvent) => {
    event.preventDefault()
    if (!link.trim()) return
    setState({ status: 'loading' })
    try {
      setState({ status: 'done', result: await match({ data: link.trim() }) })
    } catch {
      setState({ status: 'done', result: UNEXPECTED })
    }
  }

  const result = state.status === 'done' ? state.result : null

  return (
    <Card>
      <CardContent>
        <form onSubmit={submit} className="space-y-3">
          <label htmlFor="log-link" className="flex items-center gap-2 font-bold">
            <FileSearch className="size-4" aria-hidden /> Check a log
          </label>
          <p className="text-sm text-muted-foreground">
            Upload your log to{' '}
            <a href="https://mclo.gs" target="_blank" rel="noreferrer" className="text-primary underline underline-offset-4">
              mclo.gs
            </a>{' '}
            and paste the link. We look for known errors in it.
          </p>
          <Input
            id="log-link"
            type="text"
            inputMode="url"
            autoComplete="off"
            spellCheck={false}
            value={link}
            maxLength={MAX_LOG_LINK_LENGTH}
            onChange={(event) => setLink(event.target.value)}
            placeholder="https://mclo.gs/abc123"
            aria-invalid={result?.ok === false || undefined}
            aria-describedby="log-link-result"
            className="bg-background"
          />
          <Button type="submit" disabled={!link.trim() || state.status === 'loading'}>
            {state.status === 'loading' && <LoaderCircle className="animate-spin" aria-hidden />}
            Find solutions
          </Button>
        </form>

        <div id="log-link-result" aria-live="polite" className="mt-4 space-y-3">
          {result?.ok === false && <p className="text-sm text-destructive">{result.message}</p>}
          {result?.ok && result.matches.length === 0 && (
            <p className="text-sm text-muted-foreground">No known errors found in this log.</p>
          )}
          {result?.ok && result.matches.map((solution) => <SolutionCard key={solution.slug} solution={solution} />)}
        </div>
      </CardContent>
    </Card>
  )
}
