import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  ActionIcon,
  Badge,
  Button,
  Group,
  Modal,
  Stack,
  Table,
  Text,
  TextInput,
  Title,
  Textarea,
} from '@mantine/core'
import { useDisclosure } from '@mantine/hooks'
import { Pencil, Plus, Trash2 } from 'lucide-react'
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

  // Delete modal
  const [deleteTarget, setDeleteTarget] = useState<Form | null>(null)
  const [deleting, setDeleting] = useState(false)

  useEffect(() => {
    if (!workspace) return
    setLoading(true)
    apiFetch(`/api/workspaces/${workspace.id}/forms`)
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((data: Form[]) => setForms(data))
      .catch(() => showToast('error', t('forms.loadError')))
      .finally(() => setLoading(false))
  }, [workspace, showToast, t])

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

  return (
    <AppLayout>
      <Group justify="space-between" mb="lg">
        <Title order={2}>{t('nav.forms')}</Title>
        <Button leftSection={<Plus size={16} />} onClick={openCreate}>
          {t('forms.create')}
        </Button>
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
                  <Badge color={STATUS_COLORS[form.status]}>{form.status}</Badge>
                </Table.Td>
                <Table.Td>
                  {form.currentRevision ? `v${form.currentRevision.version}` : '—'}
                </Table.Td>
                <Table.Td>{new Date(form.createdAt).toLocaleDateString()}</Table.Td>
                <Table.Td>
                  <Group gap={4} justify="flex-end">
                    <ActionIcon variant="subtle" aria-label={t('common.edit')}>
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
    </AppLayout>
  )
}
