import { screen, waitFor, fireEvent, within } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { renderWithProviders } from '@/test/render'
import { FormsPage } from './FormsPage'

// ---------------------------------------------------------------------------
// Module mocks
// ---------------------------------------------------------------------------

vi.mock('@/lib/api', () => ({
  apiFetch: vi.fn(),
}))

vi.mock('@/context/WorkspaceContext', () => ({
  useWorkspace: vi.fn(),
}))

vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string, opts?: Record<string, unknown>) => {
      if (opts?.title) return `${key}:${opts.title}`
      return key
    },
  }),
}))

vi.mock('@/components/toast', () => ({
  useToast: () => ({ showToast: vi.fn() }),
}))

vi.mock('@/components/layout', () => ({
  AppLayout: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}))

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

import { apiFetch } from '@/lib/api'
import { useWorkspace } from '@/context/WorkspaceContext'

const mockApiFetch = vi.mocked(apiFetch)
const mockUseWorkspace = vi.mocked(useWorkspace)

const WORKSPACE = { id: 'ws-1', name: 'Test WS', slug: 'test-ws' }

function makeForm(
  overrides: Partial<{
    id: string
    title: string
    status: 'draft' | 'published' | 'archived'
    currentRevision: { id: string; version: number; createdAt: string } | null
    createdAt: string
  }> = {},
) {
  return {
    id: 'form-1',
    title: 'My Form',
    description: null,
    status: 'draft' as const,
    currentRevision: null,
    createdAt: '2024-01-01T00:00:00Z',
    ...overrides,
  }
}

function mockJson(data: unknown, ok = true): Response {
  return {
    ok,
    json: () => Promise.resolve(data),
    status: ok ? 200 : 422,
  } as unknown as Response
}

beforeEach(() => {
  vi.resetAllMocks()
  mockUseWorkspace.mockReturnValue({ workspace: WORKSPACE })
})

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('FormsPage', () => {
  it('shows loading state then renders form list', async () => {
    mockApiFetch.mockResolvedValueOnce({
      ok: true,
      json: () =>
        new Promise((res) => setTimeout(() => res([makeForm({ title: 'Alpha Form' })]), 10)),
    } as Response)

    renderWithProviders(<FormsPage />)

    expect(screen.getByText('common.loading')).toBeInTheDocument()

    await waitFor(() => expect(screen.getByText('Alpha Form')).toBeInTheDocument())
  })

  it('shows empty state when no forms', async () => {
    mockApiFetch.mockResolvedValueOnce(mockJson([]))

    renderWithProviders(<FormsPage />)

    await waitFor(() => expect(screen.getByText('forms.empty')).toBeInTheDocument())
    expect(screen.getByText('forms.createFirst')).toBeInTheDocument()
  })

  it('renders multiple forms in a table', async () => {
    mockApiFetch.mockResolvedValueOnce(
      mockJson([
        makeForm({ id: 'f1', title: 'Form One' }),
        makeForm({ id: 'f2', title: 'Form Two', status: 'published' }),
      ]),
    )

    renderWithProviders(<FormsPage />)

    await waitFor(() => expect(screen.getByText('Form One')).toBeInTheDocument())
    expect(screen.getByText('Form Two')).toBeInTheDocument()
  })

  it('shows revision version when currentRevision is set', async () => {
    mockApiFetch.mockResolvedValueOnce(
      mockJson([
        makeForm({
          currentRevision: { id: 'rev-1', version: 3, createdAt: '2024-01-01T00:00:00Z' },
        }),
      ]),
    )

    renderWithProviders(<FormsPage />)

    await waitFor(() => expect(screen.getByText('v3')).toBeInTheDocument())
  })

  it('opens create modal and submits new form', async () => {
    const newForm = makeForm({ id: 'f-new', title: 'New Form' })
    mockApiFetch
      .mockResolvedValueOnce(mockJson([])) // initial list
      .mockResolvedValueOnce(mockJson(newForm, true)) // POST

    renderWithProviders(<FormsPage />)

    await waitFor(() => expect(screen.getByText('forms.empty')).toBeInTheDocument())

    fireEvent.click(screen.getAllByText('forms.create')[0])
    await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument())

    fireEvent.change(screen.getByRole('textbox', { name: 'forms.title' }), {
      target: { value: 'New Form' },
    })
    fireEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', { name: 'forms.create' }),
    )

    await waitFor(() => expect(screen.getByText('New Form')).toBeInTheDocument())
    expect(mockApiFetch).toHaveBeenCalledWith(
      `/api/workspaces/${WORKSPACE.id}/forms`,
      expect.objectContaining({ method: 'POST' }),
    )
  })

  it('opens delete confirmation modal and removes form', async () => {
    const form = makeForm({ id: 'f-del', title: 'Delete Me' })
    mockApiFetch
      .mockResolvedValueOnce(mockJson([form])) // initial list
      .mockResolvedValueOnce(mockJson(null, true)) // DELETE 204

    renderWithProviders(<FormsPage />)

    await waitFor(() => expect(screen.getByText('Delete Me')).toBeInTheDocument())

    fireEvent.click(screen.getByLabelText('common.delete'))
    await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument())

    fireEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', { name: 'common.delete' }),
    )

    await waitFor(() => expect(screen.queryByText('Delete Me')).not.toBeInTheDocument())
  })
})
