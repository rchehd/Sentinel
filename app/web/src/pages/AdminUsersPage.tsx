import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  Title,
  Text,
  Button,
  TextInput,
  PasswordInput,
  Checkbox,
  Stack,
  Group,
  Table,
  Badge,
  Alert,
  Paper,
  ActionIcon,
  Tooltip,
  Divider,
} from '@mantine/core'
import { AppLayout } from '@/components/layout'
import { useToast } from '@/components/toast'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

interface UserRow {
  id: string
  email: string
  username: string
  firstName: string | null
  lastName: string | null
  roles: string[]
  isActive: boolean
  mustChangePassword: boolean
}

function generatePassword(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%'
  return Array.from({ length: 16 }, () => chars[Math.floor(Math.random() * chars.length)]).join('')
}

export function AdminUsersPage() {
  const { t } = useTranslation()
  const { showToast } = useToast()

  const [users, setUsers] = useState<UserRow[]>([])
  const [loadingUsers, setLoadingUsers] = useState(true)

  const [email, setEmail] = useState('')
  const [username, setUsername] = useState('')
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [password, setPassword] = useState('')
  const [mustChangePassword, setMustChangePassword] = useState(true)
  const [creating, setCreating] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [createdPassword, setCreatedPassword] = useState<string | null>(null)
  const [copied, setCopied] = useState(false)

  const clearError = (field: string) =>
    setErrors((prev) => (prev[field] ? { ...prev, [field]: '' } : prev))

  const fetchUsers = async () => {
    setLoadingUsers(true)
    try {
      const res = await fetch(`${API_URL}/api/admin/users`, { credentials: 'include' })
      if (res.ok) {
        setUsers(await res.json())
      }
    } finally {
      setLoadingUsers(false)
    }
  }

  useEffect(() => {
    void fetchUsers()
  }, [])

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}
    if (!email) newErrors.email = t('auth.emailRequired')
    if (!username) newErrors.username = t('auth.usernameRequired')

    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
      return
    }

    setCreating(true)
    setCreatedPassword(null)

    try {
      const body: Record<string, unknown> = {
        email,
        username,
        mustChangePassword,
      }
      if (firstName) body.firstName = firstName
      if (lastName) body.lastName = lastName
      if (password) body.password = password

      const res = await fetch(`${API_URL}/api/admin/users`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      })

      if (!res.ok) {
        const data = await res.json()
        throw new Error(data.detail || data.error || t('common.error'))
      }

      const data = await res.json()

      if (data.generatedPassword) {
        setCreatedPassword(data.generatedPassword)
      }

      showToast('success', t('admin.userCreated'), t('admin.userCreatedDesc'))
      setEmail('')
      setUsername('')
      setFirstName('')
      setLastName('')
      setPassword('')
      setMustChangePassword(true)
      setErrors({})
      await fetchUsers()
    } catch (err) {
      const message = err instanceof Error ? err.message : t('common.error')
      showToast('error', t('common.error'), message)
    } finally {
      setCreating(false)
    }
  }

  const handleCopy = () => {
    if (createdPassword) {
      void navigator.clipboard.writeText(createdPassword)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    }
  }

  return (
    <AppLayout>
      <Title order={2} mb="lg">
        {t('admin.users')}
      </Title>

      {createdPassword && (
        <Alert color="green" mb="lg" title={t('admin.generatedPassword')} withCloseButton onClose={() => setCreatedPassword(null)}>
          <Group gap="xs">
            <Text ff="monospace" fw={600}>
              {createdPassword}
            </Text>
            <Button size="xs" variant="light" onClick={handleCopy}>
              {copied ? t('admin.copiedPassword') : t('admin.copyPassword')}
            </Button>
          </Group>
        </Alert>
      )}

      <Paper withBorder radius="md" p="md" mb="xl">
        <Title order={4} mb="md">
          {t('admin.createUser')}
        </Title>

        <form onSubmit={handleCreate} noValidate>
          <Stack>
            <Group grow>
              <TextInput
                required
                label={t('auth.email')}
                placeholder="user@example.com"
                type="email"
                value={email}
                error={errors.email}
                onChange={(e) => {
                  setEmail(e.currentTarget.value)
                  clearError('email')
                }}
              />
              <TextInput
                required
                label={t('auth.username')}
                placeholder={t('auth.username')}
                value={username}
                error={errors.username}
                onChange={(e) => {
                  setUsername(e.currentTarget.value)
                  clearError('username')
                }}
              />
            </Group>

            <Group grow>
              <TextInput
                label={t('auth.firstName')}
                placeholder={t('auth.firstName')}
                value={firstName}
                onChange={(e) => setFirstName(e.currentTarget.value)}
              />
              <TextInput
                label={t('auth.lastName')}
                placeholder={t('auth.lastName')}
                value={lastName}
                onChange={(e) => setLastName(e.currentTarget.value)}
              />
            </Group>

            <Group align="flex-end">
              <PasswordInput
                style={{ flex: 1 }}
                label={t('auth.password')}
                placeholder={`${t('auth.passwordMinLength')} — ${t('admin.generatePassword').toLowerCase()}`}
                value={password}
                onChange={(e) => setPassword(e.currentTarget.value)}
              />
              <Button
                variant="default"
                onClick={() => setPassword(generatePassword())}
              >
                {t('admin.generatePassword')}
              </Button>
            </Group>

            <Checkbox
              label={t('admin.requirePasswordChange')}
              checked={mustChangePassword}
              onChange={(e) => setMustChangePassword(e.currentTarget.checked)}
            />

            <Button type="submit" loading={creating} w="fit-content">
              {t('admin.createUser')}
            </Button>
          </Stack>
        </form>
      </Paper>

      <Divider mb="md" />

      <Table striped highlightOnHover>
        <Table.Thead>
          <Table.Tr>
            <Table.Th>Email</Table.Th>
            <Table.Th>{t('auth.username')}</Table.Th>
            <Table.Th>Roles</Table.Th>
            <Table.Th>Status</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          {loadingUsers ? (
            <Table.Tr>
              <Table.Td colSpan={4}>
                <Text c="dimmed" ta="center">
                  {t('common.loading')}
                </Text>
              </Table.Td>
            </Table.Tr>
          ) : users.length === 0 ? (
            <Table.Tr>
              <Table.Td colSpan={4}>
                <Text c="dimmed" ta="center">
                  {t('admin.noUsers')}
                </Text>
              </Table.Td>
            </Table.Tr>
          ) : (
            users.map((u) => (
              <Table.Tr key={u.id}>
                <Table.Td>{u.email}</Table.Td>
                <Table.Td>{u.username}</Table.Td>
                <Table.Td>
                  {u.roles.includes('ROLE_SUPER_ADMIN') ? (
                    <Badge color="red">Super Admin</Badge>
                  ) : (
                    <Badge color="blue">User</Badge>
                  )}
                </Table.Td>
                <Table.Td>
                  <Group gap="xs">
                    {u.isActive ? (
                      <Badge color="green" variant="light">Active</Badge>
                    ) : (
                      <Badge color="gray" variant="light">Inactive</Badge>
                    )}
                    {u.mustChangePassword && (
                      <Badge color="orange" variant="light">Must change pw</Badge>
                    )}
                  </Group>
                </Table.Td>
              </Table.Tr>
            ))
          )}
        </Table.Tbody>
      </Table>
    </AppLayout>
  )
}
