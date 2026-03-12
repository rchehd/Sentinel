import { createContext, lazy, Suspense, useContext, useEffect, useRef, useState } from 'react'
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { PageLoader } from '@/components/loader'
import { type AppMode, ModeContext } from '@/context/ModeContext'
import { AuthProvider, useAuth } from '@/context/AuthContext'
import { WorkspacesProvider, WorkspaceLayout } from '@/context/WorkspaceContext'
import { apiFetch } from '@/lib/api'

const LoginPage = lazy(() => import('@/pages/LoginPage').then((m) => ({ default: m.LoginPage })))
const RegisterPage = lazy(() =>
  import('@/pages/RegisterPage').then((m) => ({ default: m.RegisterPage })),
)
const ActivatePage = lazy(() =>
  import('@/pages/ActivatePage').then((m) => ({ default: m.ActivatePage })),
)
const HomePage = lazy(() => import('@/pages/HomePage').then((m) => ({ default: m.HomePage })))
const DashboardPage = lazy(() =>
  import('@/pages/DashboardPage').then((m) => ({ default: m.DashboardPage })),
)
const RegisterCheckEmailPage = lazy(() =>
  import('@/pages/RegisterCheckEmailPage').then((m) => ({ default: m.RegisterCheckEmailPage })),
)
const SetupPage = lazy(() => import('@/pages/SetupPage').then((m) => ({ default: m.SetupPage })))
const ChangePasswordPage = lazy(() =>
  import('@/pages/ChangePasswordPage').then((m) => ({ default: m.ChangePasswordPage })),
)
const AdminUsersPage = lazy(() =>
  import('@/pages/AdminUsersPage').then((m) => ({ default: m.AdminUsersPage })),
)
const FormsPage = lazy(() =>
  import('@/pages/FormsPage').then((m) => ({ default: m.FormsPage })),
)

// ---------------------------------------------------------------------------
// Setup context — shared between the provider and route guards below
// ---------------------------------------------------------------------------

interface SetupState {
  configured: boolean | null  // null = still checking
  mode: AppMode | null
}

const SetupContext = createContext<SetupState>({ configured: null, mode: null })

/**
 * Fetches /api/setup/status on mount and exposes the result via SetupContext.
 *
 * Rendering strategy — parallel initialisation with a single overlay:
 *  1. Children are rendered immediately so AuthProvider's /api/me and
 *     WorkspacesProvider's /api/workspaces requests fire in parallel with the
 *     setup check rather than sequentially.
 *  2. A full-screen PageLoader overlays everything while the setup check is in
 *     flight. loaderVisible is decoupled from `configured` with a 150 ms delay
 *     so that lazy-loaded JS chunks (triggered when `configured` flips) finish
 *     loading before the overlay fades out — preventing a white-screen flash.
 *  3. Suspense fallback is null: the overlay already covers any suspension that
 *     occurs during the initial render; a separate Suspense spinner would bleed
 *     through as the overlay fades and cause a visible text-then-spinner flicker.
 */
function SetupProvider({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation()
  const [state, setState] = useState<SetupState>({ configured: null, mode: null })
  const [loaderVisible, setLoaderVisible] = useState(true)
  const didCheck = useRef(false)

  useEffect(() => {
    if (didCheck.current) return
    didCheck.current = true

    apiFetch('/api/setup/status')
      .then((res) => (res.ok ? res.json() : null))
      .then((data: { configured: boolean; mode: AppMode } | null) => {
        setState(data ? { configured: data.configured, mode: data.mode } : { configured: true, mode: null })
        // Small delay so lazy chunks finish loading before the overlay fades out,
        // preventing a white-screen flash between the two loading phases.
        setTimeout(() => setLoaderVisible(false), 150)
      })
      .catch(() => {
        setState({ configured: true, mode: null })
        setTimeout(() => setLoaderVisible(false), 150)
      })
  }, [])

  return (
    <>
      <PageLoader variant="full" visible={loaderVisible} text={t('common.initializingCore')} />
      <SetupContext.Provider value={state}>
        <ModeContext.Provider value={{ mode: state.mode }}>
          <Suspense fallback={null}>{children}</Suspense>
        </ModeContext.Provider>
      </SetupContext.Provider>
    </>
  )
}

// ---------------------------------------------------------------------------
// Route guards — used as layout routes (render <Outlet /> or redirect)
// ---------------------------------------------------------------------------

/** Only accessible when setup has NOT been completed yet. */
function SetupOnlyRoute() {
  const { configured } = useContext(SetupContext)
  if (configured === null) return null
  return configured ? <Navigate to="/login" replace /> : <Outlet />
}

/** Only accessible when setup IS complete. */
function ConfiguredRoute() {
  const { configured } = useContext(SetupContext)
  if (configured === null) return null
  return configured ? <Outlet /> : <Navigate to="/setup/admin" replace />
}

/** Only accessible when the user is authenticated. Redirects to /login otherwise. */
function AuthGuard() {
  const { user, loading } = useAuth()
  if (loading) return null
  return user ? <Outlet /> : <Navigate to="/login" replace />
}

// ---------------------------------------------------------------------------
// App
// ---------------------------------------------------------------------------

function App() {
  return (
    <BrowserRouter>
      <SetupProvider>
        <AuthProvider>
            <Routes>
              <Route element={<SetupOnlyRoute />}>
                <Route path="/setup/admin" element={<SetupPage />} />
              </Route>

              <Route element={<ConfiguredRoute />}>
                <Route path="/login" element={<LoginPage />} />
                <Route path="/register" element={<RegisterPage />} />
                <Route path="/register/check-email" element={<RegisterCheckEmailPage />} />
                <Route path="/activate/:token" element={<ActivatePage />} />

                <Route element={<AuthGuard />}>
                  <Route element={<WorkspacesProvider />}>
                    <Route path="/:slug" element={<WorkspaceLayout />}>
                      <Route index element={<Navigate to="home" replace />} />
                      <Route path="home" element={<HomePage />} />
                      <Route path="dashboard" element={<DashboardPage />} />
                      <Route path="forms" element={<FormsPage />} />
                    </Route>
                  </Route>
                  <Route path="/change-password" element={<ChangePasswordPage />} />
                  <Route path="/admin/users" element={<AdminUsersPage />} />
                </Route>

                <Route path="*" element={<Navigate to="/login" replace />} />
              </Route>
            </Routes>
          </AuthProvider>
      </SetupProvider>
    </BrowserRouter>
  )
}

export default App
