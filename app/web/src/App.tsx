import { createContext, lazy, Suspense, useContext, useEffect, useRef, useState } from 'react'
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom'
import { PageLoader } from '@/components/loader'
import { type AppMode, ModeContext } from '@/context/ModeContext'
import { AuthProvider, useAuth } from '@/context/AuthContext'
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

// ---------------------------------------------------------------------------
// Setup context — shared between the provider and route guards below
// ---------------------------------------------------------------------------

interface SetupState {
  configured: boolean
  mode: AppMode | null
}

const SetupContext = createContext<SetupState>({ configured: true, mode: null })

function SetupProvider({ children }: { children: React.ReactNode }) {
  const [checked, setChecked] = useState(false)
  const [state, setState] = useState<SetupState>({ configured: true, mode: null })
  const didCheck = useRef(false)

  useEffect(() => {
    if (didCheck.current) return
    didCheck.current = true

    apiFetch('/api/setup/status')
      .then((res) => (res.ok ? res.json() : null))
      .then((data: { configured: boolean; mode: AppMode } | null) => {
        if (data) setState({ configured: data.configured, mode: data.mode })
      })
      .catch(() => {})
      .finally(() => setChecked(true))
  }, [])

  if (!checked) return <PageLoader variant="full" visible />

  return (
    <SetupContext.Provider value={state}>
      <ModeContext.Provider value={{ mode: state.mode }}>{children}</ModeContext.Provider>
    </SetupContext.Provider>
  )
}

// ---------------------------------------------------------------------------
// Route guards — used as layout routes (render <Outlet /> or redirect)
// ---------------------------------------------------------------------------

/** Only accessible when setup has NOT been completed yet. */
function SetupOnlyRoute() {
  const { configured } = useContext(SetupContext)
  return configured ? <Navigate to="/login" replace /> : <Outlet />
}

/** Only accessible when setup IS complete. */
function ConfiguredRoute() {
  const { configured } = useContext(SetupContext)
  return configured ? <Outlet /> : <Navigate to="/setup/admin" replace />
}

/** Only accessible when the user is authenticated. Redirects to /login otherwise. */
function AuthGuard() {
  const { user, loading } = useAuth()
  if (loading) return <PageLoader variant="full" visible />
  return user ? <Outlet /> : <Navigate to="/login" replace />
}

// ---------------------------------------------------------------------------
// App
// ---------------------------------------------------------------------------

function App() {
  const { t } = useTranslation()

  return (
    <BrowserRouter>
      <Suspense fallback={<PageLoader variant="route" visible />}>
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
                  <Route path="/home" element={<HomePage />} />
                  <Route path="/dashboard" element={<DashboardPage />} />
                  <Route path="/change-password" element={<ChangePasswordPage />} />
                  <Route path="/admin/users" element={<AdminUsersPage />} />
                </Route>

                <Route path="*" element={<Navigate to="/login" replace />} />
              </Route>
            </Routes>
          </AuthProvider>
        </SetupProvider>
      </Suspense>
    </BrowserRouter>
  )
}

export default App
