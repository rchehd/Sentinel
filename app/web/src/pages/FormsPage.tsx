import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  ActionIcon,
  Badge,
  Button,
  FileButton,
  Group,
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
import { Download, FileUp, Pencil, Plus, Trash2 } from 'lucide-react'
import { AppLayout } from '@/components/layout'
import { useToast } from '@/components/toast'
import { useWorkspace } from '@/context/WorkspaceContext'
import { apiFetch } from '@/lib/api'

interface FormRevision {
  id: string
  version: number
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
  const [importFile, setImportFile] = useState<File | null>(null)
  const [importing, setImporting] = useState(false)
  const importResetRef = useRef<() => void>(null)

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

  function closeEdit() {
    setEditTarget(null)
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
      closeEdit()
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
      const blob = await res.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.${format === 'yaml' ? 'yaml' : 'json'}`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      showToast('error', t('forms.exportError'))
    }
  }

  async function handleImport() {
    if (!workspace || !importFile) return

    const ext = importFile.name.split('.').pop()?.toLowerCase()
    const format = ext === 'yaml' || ext === 'yml' ? 'yaml' : 'json'

    if (!['json', 'yaml', 'yml'].includes(ext ?? '')) {
      showToast('error', t('forms.importFileInvalid'))
      return
    }

    setImporting(true)
    try {
      const content = await importFile.text()
      const res = await apiFetch(`/api/workspaces/${workspace.id}/forms/import`, {
        method: 'POST',
        json: { content, format },
      })
      if (!res.ok) {
        const err = await res.json().catch(() => ({}))
        throw new Error((err as { error?: string }).error ?? 'error')
      }
      const form: Form = await res.json()
      setForms((prev) => [form, ...prev])
      setImportFile(null)
      importResetRef.current?.()
      closeImport()
      showToast('success', t('forms.importSuccess', { title: form.title }))
    } catch {
      showToast('error', t('forms.importError'))
    } finally {
      setImporting(false)
    }
  }

  const statusOptions: { value: Form['status']; label: string }[] = [
    { value: 'draft', label: t('forms.statusDraft') },
    { value: 'published', label: t('forms.statusPublished') },
    { value: 'archived', label: t('forms.statusArchived') },
  ]

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
              <Table.Th>{t('forms.title')}</Table.Th>
              <Table.Th>{t('forms.status')}</Table.Th>
              <Table.Th>{t('forms.revision')}</Table.Th>
              <Table.Th>{t('forms.createdAt')}</Table.Th>
              <Table.Th />
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            {forms.map((form) => (
              <Table.Tr key={form.id}>
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
                    <Tooltip label={t('forms.exportJson')} withArrow>
                      <ActionIcon
                        variant="subtle"
                        aria-label={t('forms.exportJson')}
                        onClick={() => handleExport(form, 'json')}
                      >
                        <Download size={16} />
                      </ActionIcon>
                    </Tooltip>
                    <Tooltip label={t('forms.exportYaml')} withArrow>
                      <ActionIcon
                        variant="subtle"
                        aria-label={t('forms.exportYaml')}
                        onClick={() => handleExport(form, 'yaml')}
                      >
                        <Download size={16} />
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
        onClose={closeEdit}
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
            <Button variant="default" onClick={closeEdit}>
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
        onClose={closeImport}
        title={t('forms.importTitle')}
        transitionProps={{ duration: 0 }}
      >
        <Stack gap="sm">
          <FileButton resetRef={importResetRef} onChange={setImportFile} accept=".json,.yaml,.yml">
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
            <Button variant="default" onClick={closeImport}>
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
    </AppLayout>
  )
}
