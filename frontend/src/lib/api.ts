export async function api(path: string, init: RequestInit = {}) {
  const base = process.env.API_URL ?? 'http://localhost:8100'
  return fetch(`${base}/api${path}`, {
    ...init,
    headers: { Accept: 'application/json', ...init.headers },
    signal: AbortSignal.timeout(8000),
  })
}

export async function json<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await api(path, init)
  if (!response.ok) throw new Error(`API responded with ${response.status} for ${path}`)
  return response.json()
}

// Returns null on a 404 so routes can show their own not-found page.
export async function jsonOrNull<T>(path: string): Promise<T | null> {
  const response = await api(path)
  if (response.status === 404) return null
  if (!response.ok) throw new Error(`API responded with ${response.status} for ${path}`)
  return response.json()
}
