import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { MantineProvider } from '@mantine/core'
import '@mantine/core/styles.css'
import './i18n'
import './index.css'
import './styles/global.scss'
import { theme } from './theme'
import { ToastProvider } from './components/toast'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <MantineProvider theme={theme}>
      <ToastProvider>
        <App />
      </ToastProvider>
    </MantineProvider>
  </StrictMode>,
)
