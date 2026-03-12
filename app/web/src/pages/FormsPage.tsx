import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  ActionIcon,
  Badge,
  Button,
  Checkbox,
  FileButton,
  Group,
  Menu,
  Modal,
  Select,
  Stack,
  Table,
  Text,
  TextInput,
  Title,
  Textarea,
  Tooltip,
} from '@mantine/core'
import { useDisclosure } from '@mantine/hooks'
import { ChevronDown, Clock, Download, FileUp, Pencil, Plus, Trash2 } from 'lucide-react'
import { AppLayout } from '@/components/layout'
import { useToast } from '@/components/toast'
import { useWorkspace } from '@/context/WorkspaceContext'
import { apiFetch } from '@/lib/api'

interface FormRevision {
  id: string
  version: number
  title: string | null
  createdAt: string
}

interface Form {
  id: string
  title: string
  description: string | null
  status: 'draft' | 'published' | 'archived'
  currentRevision: FormRevision | null
  createdAt: string
}

const STATUS_COLORS: Record<Form['status'], string> = {
  draft: 'gray',
  published: 'green',
  archived: 'red',
}

export function FormsPage() {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const workspaceCtx = useWorkspace()
  const workspace = workspaceCtx?.workspace

  const [forms, setForms] = useState<Form[]>([])
  const [loading, setLoading] = useState(true)

  // Row selection for bulk operations
  const [selected, setSelected] = useState<Set<string>>(new Set())

  // Create modal
  const [createOpened, { open: openCreate, close: closeCreate }] = useDisclosure(false)
  const [newTitle, setNewTitle] = useState('')
  const [newDescription, setNewDescription] = useState('')
  const [creating, setCreating] = useState(false)

  // Edit modal
  const [editTarget, setEditTarget] = useState<Form | null>(null)
  const [editTitle, setEditTitle] = useState('')
  const [editDescription, setEditDescription] = useState('')
  const [editStatus, setEditStatus] = useState<Form['status']>('draft')
  const [editing, setEditing] = useState(false)

  // Delete modal
  const [deleteTarget, setDeleteTarget] = useState<Form | null>(null)
  const [deleting, setDeleting] = useState(false)

  // Import modal
  const [importOpened, { open: openImport, close: closeImport }] = useDisclosure(false)
  const [importFormat, setImportFormat] = useState<'json' | 'yaml'>('json')
  const [importFile, setImportFile] = useState<File | null>(null)
  const [importing, setImporting] = useState(false)
  const importResetRef = useRef<() => void>(null)

  // Export format modal (single form) — kept for future modal variant if needed
  // Currently handled inline via Menu in the table row

  // Revision history modal
  const [revisionForm, setRevisionForm] = useState<Form | null>(null)
  const [revisions, setRevisions] = useState<FormRevision[]>([])
  const [revisionsLoading, setRevisionsLoading] = useState(false)
  const [restoringId, setRestoringId] = useState<string | null>(null)

  useEffect(() => {
    if (!workspace) return
    setLoading(true)
    apiFetch(`/api/workspaces/${workspace.id}/forms`)
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((data: Form[]) => setForms(data))
      .catch(() => showToast('error', t('forms.loadError')))
      .finally(() => setLoading(false))
  }, [workspace, showToast, t])

  function openEdit(form: Form) {
    setEditTarget(form)
    setEditTitle(form.title)
    setEditDescription(form.description ?? '')
    setEditStatus(form.status)
  }

  async function openRevisions(form: Form) {
    if (!workspace) return
    setRevisionForm(form)
    setRevisions([])
    setRevisionsLoading(true)
    try {
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms/${form.id}/revisions`)
      if (!res.ok) throw new Error()
      setRevisions(await res.json())
    } catch {
      showToast('error', t('forms.loadError'))
    } finally {
      setRevisionsLoading(false)
    }
  }

  function toggleSelect(id: string) {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(id)) {
        next.delete(id)
      } else {
        next.add(id)
      }
      return next
    })
  }

  function toggleSelectAll() {
    setSelected((prev) =>
      prev.size === forms.length ? new Set() : new Set(forms.map((f) => f.id)),
    )
  }

  async function handleCreate() {
    if (!workspace || !newTitle.trim()) return
    setCreating(true)
    try {
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms`, {
        method: 'POST',
        json: { title: newTitle.trim(), description: newDescription.trim() || null },
      })
      if (!res.ok) throw new Error()
      const form: Form = await res.json()
      setForms((prev) => [form, ...prev])
      setNewTitle('')
      setNewDescription('')
      closeCreate()
      showToast('success', t('forms.createSuccess', { title: form.title }))
    } catch {
      showToast('error', t('forms.createError'))
    } finally {
      setCreating(false)
    }
  }

  async function handleEdit() {
    if (!workspace || !editTarget || !editTitle.trim()) return
    setEditing(true)
    try {
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms/${editTarget.id}`, {
        method: 'PATCH',
        json: {
          title: editTitle.trim(),
          description: editDescription.trim() || null,
          status: editStatus,
        },
      })
      if (!res.ok) throw new Error()
      const updated: Form = await res.json()
      setForms((prev) => prev.map((f) => (f.id === updated.id ? updated : f)))
      setEditTarget(null)
      showToast('success', t('forms.editSuccess', { title: updated.title }))
    } catch {
      showToast('error', t('forms.editError'))
    } finally {
      setEditing(false)
    }
  }

  async function handleDelete() {
    if (!workspace || !deleteTarget) return
    setDeleting(true)
    try {
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms/${deleteTarget.id}`, {
        method: 'DELETE',
      })
      if (!res.ok) throw new Error()
      setForms((prev) => prev.filter((f) => f.id !== deleteTarget.id))
      setSelected((prev) => {
        const next = new Set(prev)
        next.delete(deleteTarget.id)
        return next
      })
      setDeleteTarget(null)
      showToast('success', t('forms.deleteSuccess', { title: deleteTarget.title }))
    } catch {
      showToast('error', t('forms.deleteError'))
    } finally {
      setDeleting(false)
    }
  }

  async function handleExport(form: Form, format: 'json' | 'yaml') {
    if (!workspace) return
    try {
      const res = await apiFetch(
        `/api/workspaces/${workspace.id}/forms/${form.id}/export?format=${format}`,
      )
      if (!res.ok) throw new Error()
      triggerDownload(await res.blob(), `${slugify(form.title)}.${format}`)
    } catch {
      showToast('error', t('forms.exportError'))
    }
  }

  async function handleBulkExport(format: 'json' | 'yaml') {
    if (!workspace || selected.size === 0) return
    try {
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms/export-bulk`, {
        method: 'POST',
        json: { ids: Array.from(selected), format },
      })
      if (!res.ok) throw new Error()
      triggerDownload(await res.blob(), `forms-export.${format}`)
    } catch {
      showToast('error', t('forms.bulkExportError'))
    }
  }

  async function handleImport() {
    if (!workspace || !importFile) return

    setImporting(true)
    try {
      const content = await importFile.text()

      // Validate that the file parses successfully before sending
      try {
        if (importFormat === 'json') {
          JSON.parse(content)
        }
        // YAML parsing validation happens server-side (no bundled YAML parser)
      } catch {
        showToast('error', t('forms.importFileInvalid'))
        return
      }

      // Detect bulk vs single: bulk files have a top-level `forms` array
      const isBulk = isBulkFile(content, importFormat)
      const endpoint = isBulk
        ? `/api/workspaces/${workspace.id}/forms/import-bulk`
        : `/api/workspaces/${workspace.id}/forms/import`

      const res = await apiFetch(endpoint, {
        method: 'POST',
        json: { content, format: importFormat },
      })
      if (!res.ok) {
        const err = await res.json().catch(() => ({}))
        throw new Error((err as { error?: string }).error ?? 'error')
      }

      if (isBulk) {
        const created: Form[] = await res.json()
        setForms((prev) => [...created, ...prev])
        showToast('success', t('forms.bulkImportSuccess', { count: created.length }))
      } else {
        const form: Form = await res.json()
        setForms((prev) => [form, ...prev])
        showToast('success', t('forms.importSuccess', { title: form.title }))
      }

      setImportFile(null)
      importResetRef.current?.()
      closeImport()
    } catch {
      showToast('error', t('forms.importError'))
    } finally {
      setImporting(false)
    }
  }

  async function handleRestore(revision: FormRevision) {
    if (!workspace || !revisionForm) return
    setRestoringId(revision.id)
    try {
      const res = await apiFetch(
        `/api/workspaces/${workspace.id}/forms/${revisionForm.id}/revisions/${revision.id}/restore`,
        { method: 'POST' },
      )
      if (!res.ok) throw new Error()
      const updated: Form = await res.json()
      setForms((prev) => prev.map((f) => (f.id === updated.id ? updated : f)))
      setRevisionForm(updated)
      showToast('success', t('forms.revisionsRestoreSuccess', { version: revision.version }))
    } catch {
      showToast('error', t('forms.revisionsRestoreError'))
    } finally {
      setRestoringId(null)
    }
  }

  const statusOptions: { value: Form['status']; label: string }[] = [
    { value: 'draft', label: t('forms.statusDraft') },
    { value: 'published', label: t('forms.statusPublished') },
    { value: 'archived', label: t('forms.statusArchived') },
  ]

  const allSelected = forms.length > 0 && selected.size === forms.length
  const someSelected = selected.size > 0 && !allSelected

  return (
    <AppLayout>
      <Group justify="space-between" mb="lg">
        <Title order={2}>{t('nav.forms')}</Title>
        <Group gap="sm">
          <Button variant="default" leftSection={<FileUp size={16} />} onClick={openImport}>
            {t('forms.importButton')}
          </Button>
          <Button leftSection={<Plus size={16} />} onClick={openCreate}>
            {t('forms.create')}
          </Button>
        </Group>
      </Group>

      {/* Bulk action bar */}
      {selected.size > 0 && (
        <Group
          mb="sm"
          p="sm"
          style={{ background: 'var(--mantine-color-default)', borderRadius: 8 }}
        >
          <Text size="sm" fw={500}>
            {t('forms.selectedCount', { count: selected.size })}
          </Text>
          <Menu shadow="md">
            <Menu.Target>
              <Button
                size="xs"
                variant="light"
                leftSection={<Download size={14} />}
                rightSection={<ChevronDown size={14} />}
              >
                {t('forms.exportSelected')}
              </Button>
            </Menu.Target>
            <Menu.Dropdown>
              <Menu.Item onClick={() => handleBulkExport('json')}>
                {t('forms.bulkExportJson')}
              </Menu.Item>
              <Menu.Item onClick={() => handleBulkExport('yaml')}>
                {t('forms.bulkExportYaml')}
              </Menu.Item>
            </Menu.Dropdown>
          </Menu>
          <Button size="xs" variant="subtle" color="gray" onClick={() => setSelected(new Set())}>
            {t('common.cancel')}
          </Button>
        </Group>
      )}

      {loading ? (
        <Text c="dimmed">{t('common.loading')}</Text>
      ) : forms.length === 0 ? (
        <Stack align="center" py="xl" gap="md">
          <Text c="dimmed" size="lg">
            {t('forms.empty')}
          </Text>
          <Button leftSection={<Plus size={16} />} onClick={openCreate}>
            {t('forms.createFirst')}
          </Button>
        </Stack>
      ) : (
        <Table striped highlightOnHover withTableBorder>
          <Table.Thead>
            <Table.Tr>
              <Table.Th w={40}>
                <Checkbox
                  checked={allSelected}
                  indeterminate={someSelected}
                  onChange={toggleSelectAll}
                  aria-label={t('forms.selectAll')}
                />
              </Table.Th>
              <Table.Th>{t('forms.title')}</Table.Th>
              <Table.Th>{t('forms.status')}</Table.Th>
              <Table.Th>{t('forms.revision')}</Table.Th>
              <Table.Th>{t('forms.createdAt')}</Table.Th>
              <Table.Th />
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            {forms.map((form) => (
              <Table.Tr
                key={form.id}
                bg={selected.has(form.id) ? 'var(--mantine-color-blue-light)' : undefined}
              >
                <Table.Td>
                  <Checkbox
                    checked={selected.has(form.id)}
                    onChange={() => toggleSelect(form.id)}
                    aria-label={form.title}
                  />
                </Table.Td>
                <Table.Td>{form.title}</Table.Td>
                <Table.Td>
                  <Badge color={STATUS_COLORS[form.status]}>
                    {t(`forms.status${form.status.charAt(0).toUpperCase() + form.status.slice(1)}`)}
                  </Badge>
                </Table.Td>
                <Table.Td>
                  {form.currentRevision ? `v${form.currentRevision.version}` : '—'}
                </Table.Td>
                <Table.Td>{new Date(form.createdAt).toLocaleDateString()}</Table.Td>
                <Table.Td>
                  <Group gap={4} justify="flex-end">
                    <Menu withinPortal position="bottom-end" shadow="sm">
                      <Tooltip label={t('forms.exportButton')} withArrow>
                        <Menu.Target>
                          <ActionIcon variant="subtle" aria-label={t('forms.exportButton')}>
                            <Download size={16} />
                          </ActionIcon>
                        </Menu.Target>
                      </Tooltip>
                      <Menu.Dropdown>
                        <Menu.Label>{t('forms.exportFormat')}</Menu.Label>
                        <Menu.Item onClick={() => handleExport(form, 'json')}>
                          {t('forms.exportJson')}
                        </Menu.Item>
                        <Menu.Item onClick={() => handleExport(form, 'yaml')}>
                          {t('forms.exportYaml')}
                        </Menu.Item>
                      </Menu.Dropdown>
                    </Menu>
                    <Tooltip label={t('forms.revisionsTitle')} withArrow>
                      <ActionIcon
                        variant="subtle"
                        aria-label={t('forms.revisionsTitle')}
                        onClick={() => openRevisions(form)}
                      >
                        <Clock size={16} />
                      </ActionIcon>
                    </Tooltip>
                    <ActionIcon
                      variant="subtle"
                      aria-label={t('common.edit')}
                      onClick={() => openEdit(form)}
                    >
                      <Pencil size={16} />
                    </ActionIcon>
                    <ActionIcon
                      variant="subtle"
                      color="red"
                      aria-label={t('common.delete')}
                      onClick={() => setDeleteTarget(form)}
                    >
                      <Trash2 size={16} />
                    </ActionIcon>
                  </Group>
                </Table.Td>
              </Table.Tr>
            ))}
          </Table.Tbody>
        </Table>
      )}

      {/* Create modal */}
      <Modal
        opened={createOpened}
        onClose={closeCreate}
        title={t('forms.createTitle')}
        transitionProps={{ duration: 0 }}
      >
        <Stack gap="sm">
          <TextInput
            label={t('forms.title')}
            placeholder={t('forms.titlePlaceholder')}
            value={newTitle}
            onChange={(e) => setNewTitle(e.currentTarget.value)}
            required
            data-autofocus
          />
          <Textarea
            label={t('forms.descriptionLabel')}
            placeholder={t('forms.descriptionPlaceholder')}
            value={newDescription}
            onChange={(e) => setNewDescription(e.currentTarget.value)}
            rows={3}
          />
          <Group justify="flex-end" mt="sm">
            <Button variant="default" onClick={closeCreate}>
              {t('common.cancel')}
            </Button>
            <Button onClick={handleCreate} loading={creating} disabled={!newTitle.trim()}>
              {t('forms.create')}
            </Button>
          </Group>
        </Stack>
      </Modal>

      {/* Edit modal */}
      <Modal
        opened={editTarget !== null}
        onClose={() => setEditTarget(null)}
        title={t('forms.editTitle')}
        transitionProps={{ duration: 0 }}
      >
        <Stack gap="sm">
          <TextInput
            label={t('forms.title')}
            placeholder={t('forms.titlePlaceholder')}
            value={editTitle}
            onChange={(e) => setEditTitle(e.currentTarget.value)}
            required
            data-autofocus
          />
          <Textarea
            label={t('forms.descriptionLabel')}
            placeholder={t('forms.descriptionPlaceholder')}
            value={editDescription}
            onChange={(e) => setEditDescription(e.currentTarget.value)}
            rows={3}
          />
          <Select
            label={t('forms.status')}
            data={statusOptions}
            value={editStatus}
            onChange={(v) => setEditStatus((v as Form['status']) ?? 'draft')}
          />
          <Group justify="flex-end" mt="sm">
            <Button variant="default" onClick={() => setEditTarget(null)}>
              {t('common.cancel')}
            </Button>
            <Button onClick={handleEdit} loading={editing} disabled={!editTitle.trim()}>
              {t('common.save')}
            </Button>
          </Group>
        </Stack>
      </Modal>

      {/* Delete confirmation modal */}
      <Modal
        opened={deleteTarget !== null}
        onClose={() => setDeleteTarget(null)}
        title={t('forms.deleteTitle')}
        transitionProps={{ duration: 0 }}
      >
        <Text mb="lg">{t('forms.deleteConfirm', { title: deleteTarget?.title })}</Text>
        <Group justify="flex-end">
          <Button variant="default" onClick={() => setDeleteTarget(null)}>
            {t('common.cancel')}
          </Button>
          <Button color="red" onClick={handleDelete} loading={deleting}>
            {t('common.delete')}
          </Button>
        </Group>
      </Modal>

      {/* Import modal */}
      <Modal
        opened={importOpened}
        onClose={() => {
          closeImport()
          setImportFile(null)
          importResetRef.current?.()
        }}
        title={t('forms.importTitle')}
        transitionProps={{ duration: 0 }}
      >
        <Stack gap="sm">
          <Select
            label={t('forms.importFormatLabel')}
            data={[
              { value: 'json', label: 'JSON (.json)' },
              { value: 'yaml', label: 'YAML (.yaml / .yml)' },
            ]}
            value={importFormat}
            onChange={(v) => {
              setImportFormat((v as 'json' | 'yaml') ?? 'json')
              // Reset file when format changes to avoid type mismatch
              setImportFile(null)
              importResetRef.current?.()
            }}
          />
          <FileButton
            resetRef={importResetRef}
            onChange={setImportFile}
            accept={importFormat === 'yaml' ? '.yaml,.yml' : '.json'}
          >
            {(props) => (
              <Button variant="default" fullWidth {...props}>
                {importFile ? importFile.name : t('forms.importFilePlaceholder')}
              </Button>
            )}
          </FileButton>
          {importFile && (
            <Text size="xs" c="dimmed">
              {importFile.name} ({(importFile.size / 1024).toFixed(1)} KB)
            </Text>
          )}
          <Group justify="flex-end" mt="sm">
            <Button
              variant="default"
              onClick={() => {
                closeImport()
                setImportFile(null)
                importResetRef.current?.()
              }}
            >
              {t('common.cancel')}
            </Button>
            <Button
              leftSection={<FileUp size={16} />}
              onClick={handleImport}
              loading={importing}
              disabled={!importFile}
            >
              {t('forms.importButton')}
            </Button>
          </Group>
        </Stack>
      </Modal>

      {/* Revision history modal */}
      <Modal
        opened={revisionForm !== null}
        onClose={() => setRevisionForm(null)}
        title={t('forms.revisionsTitle')}
        size="lg"
        transitionProps={{ duration: 0 }}
      >
        {revisionsLoading ? (
          <Text c="dimmed">{t('common.loading')}</Text>
        ) : revisions.length === 0 ? (
          <Text c="dimmed">{t('forms.revisionsEmpty')}</Text>
        ) : (
          <Table>
            <Table.Thead>
              <Table.Tr>
                <Table.Th>{t('forms.revisionsVersion')}</Table.Th>
                <Table.Th>{t('forms.title')}</Table.Th>
                <Table.Th>{t('forms.revisionsDate')}</Table.Th>
                <Table.Th />
              </Table.Tr>
            </Table.Thead>
            <Table.Tbody>
              {revisions.map((rev) => {
                const isCurrent = rev.id === revisionForm?.currentRevision?.id
                return (
                  <Table.Tr key={rev.id}>
                    <Table.Td>
                      <Group gap={6}>
                        v{rev.version}
                        {isCurrent && (
                          <Badge size="xs" color="blue">
                            current
                          </Badge>
                        )}
                      </Group>
                    </Table.Td>
                    <Table.Td>{rev.title ?? '—'}</Table.Td>
                    <Table.Td>{new Date(rev.createdAt).toLocaleString()}</Table.Td>
                    <Table.Td>
                      <Button
                        size="xs"
                        variant="light"
                        disabled={isCurrent}
                        loading={restoringId === rev.id}
                        onClick={() => handleRestore(rev)}
                      >
                        {t('forms.revisionsRestore')}
                      </Button>
                    </Table.Td>
                  </Table.Tr>
                )
              })}
            </Table.Tbody>
          </Table>
        )}
      </Modal>
    </AppLayout>
  )
}

/** Slugify a title for use in a filename. */
function slugify(title: string): string {
  return title.toLowerCase().replace(/[^a-z0-9]+/g, '-')
}

/** Trigger a file download from a Blob. */
function triggerDownload(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

/**
 * Detect whether a file's content is a bulk export (has a top-level `forms`
 * array) as opposed to a single-form export.
 */
function isBulkFile(content: string, format: 'json' | 'yaml'): boolean {
  try {
    if (format === 'json') {
      const parsed = JSON.parse(content)
      return Array.isArray(parsed?.forms)
    }
    // Lightweight YAML check: look for a `forms:` key at the root level
    return /^forms\s*:/m.test(content)
  } catch {
    return false
  }
}
