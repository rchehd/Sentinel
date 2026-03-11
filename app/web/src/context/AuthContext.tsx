import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react'
import { apiFetch } from '@/lib/api'

export interface CurrentUser {
  id: string
  email: string
  username: string
  firstName: string | null
  lastName: string | null
  roles: string[]
  mustChangePassword: boolean
}

interface AuthContextValue {
  user: CurrentUser | null
  loading: boolean
  setUser: (user: CurrentUser | null) => void
  refresh: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue>({
  user: null,
  loading: true,
  setUser: () => {},
  refresh: async () => {},
})

export function useAuth(): AuthContextValue {
  return useContext(AuthContext)
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<CurrentUser | null>(null)
  const [loading, setLoading] = useState(true)
  const didFetch = useRef(false)

  const fetchMe = useCallback(async () => {
    try {
      const res = await apiFetch('/api/me')
      if (res.ok) {
        setUser(await res.json())
      } else {
        setUser(null)
      }
    } catch {
      setUser(null)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (didFetch.current) return
    didFetch.current = true
    void fetchMe()
  }, [fetchMe])

  return (
    <AuthContext.Provider value={{ user, loading, setUser, refresh: fetchMe }}>
      {children}
    </AuthContext.Provider>
  )
}
