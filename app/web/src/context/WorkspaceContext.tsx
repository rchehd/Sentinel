/* eslint-disable react-refresh/only-export-components */
import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react'
import { Navigate, Outlet, useParams } from 'react-router-dom'
import { apiFetch } from '@/lib/api'

export interface Workspace {
  id: string
  name: string
  slug: string
}

// ---------------------------------------------------------------------------
// WorkspacesContext — list of all workspaces the user belongs to
// ---------------------------------------------------------------------------

interface WorkspacesContextValue {
  workspaces: Workspace[]
  loading: boolean
  refresh: () => Promise<void>
}

const WorkspacesContext = createContext<WorkspacesContextValue>({
  workspaces: [],
  loading: true,
  refresh: async () => {},
})

export function useWorkspaces(): WorkspacesContextValue {
  return useContext(WorkspacesContext)
}

/** Layout route: fetches the workspace list once and exposes it via context. */
export function WorkspacesProvider() {
  const [workspaces, setWorkspaces] = useState<Workspace[]>([])
  const [loading, setLoading] = useState(true)
  const didFetch = useRef(false)

  const fetchWorkspaces = useCallback(async () => {
    try {
      const res = await apiFetch('/api/workspaces')
      if (res.ok) setWorkspaces(await res.json())
    } catch {
      // network error — leave empty
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (didFetch.current) return
    didFetch.current = true
    void fetchWorkspaces()
  }, [fetchWorkspaces])

  return (
    <WorkspacesContext.Provider value={{ workspaces, loading, refresh: fetchWorkspaces }}>
      <Outlet />
    </WorkspacesContext.Provider>
  )
}

// ---------------------------------------------------------------------------
// WorkspaceContext — the single workspace resolved from the :slug URL param
// ---------------------------------------------------------------------------

interface WorkspaceContextValue {
  workspace: Workspace
}

const WorkspaceContext = createContext<WorkspaceContextValue | null>(null)

export function useWorkspace(): WorkspaceContextValue | null {
  return useContext(WorkspaceContext)
}

/** Layout route: resolves :slug → Workspace and provides it via context. */
export function WorkspaceLayout() {
  const { slug } = useParams<{ slug: string }>()
  const { workspaces, loading } = useWorkspaces()

  if (loading) return null

  const workspace = workspaces.find((w) => w.slug === slug)
  if (!workspace) return <Navigate to="/login" replace />

  return (
    <WorkspaceContext.Provider value={{ workspace }}>
      <Outlet />
    </WorkspaceContext.Provider>
  )
}
