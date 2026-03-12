/* eslint-disable react-refresh/only-export-components */
import { MantineProvider } from '@mantine/core'
import { render, type RenderOptions } from '@testing-library/react'
import type { ReactElement } from 'react'

function AllProviders({ children }: { children: React.ReactNode }) {
  return <MantineProvider>{children}</MantineProvider>
}

export function renderWithProviders(ui: ReactElement, options?: RenderOptions) {
  return render(ui, { wrapper: AllProviders, ...options })
}
