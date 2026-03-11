export const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

type FetchOptions = RequestInit & { json?: unknown }

export async function apiFetch(path: string, { json, ...init }: FetchOptions = {}): Promise<Response> {
  const headers: Record<string, string> = { ...(init.headers as Record<string, string>) }

  if (json !== undefined) {
    headers['Content-Type'] = 'application/json'
    init.body = JSON.stringify(json)
  }

  const res = await fetch(`${API_URL}${path}`, {
    credentials: 'include',
    ...init,
    headers,
  })

  if (res.status === 401 && path !== '/api/login' && path !== '/api/me') {
    window.location.replace('/login')
  }

  return res
}
