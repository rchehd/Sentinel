import { createContext, lazy, Suspense, useContext, useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom'
import { PageLoader } from '@/components/loader'
import { type AppMode, ModeContext } from '@/context/ModeContext'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

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

    fetch(`${API_URL}/api/setup/status`)
      .then((res) => (res.ok ? res.json() : null))
      .then((data: { configured: boolean; mode: AppMode } | null) => {
        if (data) setState({ configured: data.configured, mode: data.mode })
      })
      .catch(() => {})
      .finally(() => setChecked(true))
  }, [])

  if (!checked) return null

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

// ---------------------------------------------------------------------------
// App
// ---------------------------------------------------------------------------

function App() {
  const { t } = useTranslation()
  const [initialLoading, setInitialLoading] = useState(true)

  useEffect(() => {
    const timer = setTimeout(() => setInitialLoading(false), 1000)
    return () => clearTimeout(timer)
  }, [])

  return (
    <>
      <PageLoader variant="full" visible={initialLoading} text={t('common.initializingCore')} />
      <BrowserRouter>
        <Suspense fallback={<PageLoader variant="route" visible />}>
          <SetupProvider>
            <Routes>
              <Route element={<SetupOnlyRoute />}>
                <Route path="/setup/admin" element={<SetupPage />} />
              </Route>

              <Route element={<ConfiguredRoute />}>
                <Route path="/login" element={<LoginPage />} />
                <Route path="/register" element={<RegisterPage />} />
                <Route path="/register/check-email" element={<RegisterCheckEmailPage />} />
                <Route path="/activate/:token" element={<ActivatePage />} />
                <Route path="/home" element={<HomePage />} />
                <Route path="/dashboard" element={<DashboardPage />} />
                <Route path="/change-password" element={<ChangePasswordPage />} />
                <Route path="/admin/users" element={<AdminUsersPage />} />
                <Route path="*" element={<Navigate to="/login" replace />} />
              </Route>
            </Routes>
          </SetupProvider>
        </Suspense>
      </BrowserRouter>
    </>
  )
}

export default App
