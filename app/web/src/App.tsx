import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { LoginPage, RegisterPage, ActivatePage, HomePage, DashboardPage } from '@/pages'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/activate/:token" element={<ActivatePage />} />
        <Route path="/home" element={<HomePage />} />
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
