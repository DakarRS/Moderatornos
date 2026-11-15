import { Check, Copy } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Button } from '#/components/ui/button'
import { discordReply } from '#/lib/solutions'
import type { Solution } from '#/lib/solutions'

export function CopyReplyButton({ solution }: { solution: Solution }) {
  const [state, setState] = useState<'idle' | 'copied' | 'failed'>('idle')

  useEffect(() => {
    if (state === 'idle') return
    const timer = setTimeout(() => setState('idle'), 2000)
    return () => clearTimeout(timer)
  }, [state])

  const copy = async () => {
    const url = `${window.location.origin}/solutions/${solution.slug}`
    try {
      await navigator.clipboard.writeText(discordReply(solution, url))
      setState('copied')
    } catch {
      setState('failed')
    }
  }

  return (
    <Button variant="outline" size="sm" onClick={copy} aria-live="polite">
      {state === 'copied' ? <Check aria-hidden /> : <Copy aria-hidden />}
      {state === 'copied' ? 'Copied' : state === 'failed' ? 'Copy failed' : 'Copy as Discord reply'}
    </Button>
  )
}
