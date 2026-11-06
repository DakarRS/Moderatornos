import { SITE_NAME } from '#/lib/site'

export const SITE_DESCRIPTION = `The people behind ${SITE_NAME}.`

// Matches --primary in light mode; Discord uses it for the embed's side bar.
const THEME_COLOR = '#4f46e5'

const MAX_DESCRIPTION = 200

// Embeds show a short line, so collapse newlines and cut on a word.
export function summarize(text: string) {
  const flat = text.replace(/\s+/g, ' ').trim()
  if (flat.length <= MAX_DESCRIPTION) return flat
  const cut = flat.slice(0, MAX_DESCRIPTION - 1)
  return `${cut.slice(0, cut.lastIndexOf(' ') > 0 ? cut.lastIndexOf(' ') : cut.length)}…`
}

// Embeds already show og:site_name, so only the tab title repeats it.
export function seo({ title, description, image }: { title?: string; description: string; image?: string | null }) {
  title ??= SITE_NAME
  return [
    { title: title === SITE_NAME ? title : `${title} · ${SITE_NAME}` },
    { name: 'description', content: description },
    { name: 'theme-color', content: THEME_COLOR },
    { property: 'og:site_name', content: SITE_NAME },
    { property: 'og:type', content: 'website' },
    { property: 'og:title', content: title },
    { property: 'og:description', content: description },
    ...(image ? [{ property: 'og:image', content: image }] : []),
    // "summary" shows the image as a small thumbnail beside the text rather than a banner.
    { name: 'twitter:card', content: 'summary' },
  ]
}
