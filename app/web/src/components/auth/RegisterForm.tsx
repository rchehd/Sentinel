import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import {
  Paper,
  Title,
  Text,
  TextInput,
  PasswordInput,
  Button,
  Divider,
  Anchor,
  Stack,
  Checkbox,
  Alert,
  Collapse,
} from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { SsoButtons } from './SsoButtons'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function RegisterForm() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const isMobile = useMediaQuery('(max-width: 480px)')

  const [createOrg, setCreateOrg] = useState(false)
  const [email, setEmail] = useState('')
  const [username, setUsername] = useState('')
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [orgLabel, setOrgLabel] = useState('')
  const [orgDomain, setOrgDomain] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState(false)
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (password.length < 8) {
      setError(t('auth.passwordMinLength'))
      return
    }

    if (password !== confirmPassword) {
      setError(t('auth.passwordsMustMatch'))
      return
    }

    if (createOrg && !orgLabel.trim()) {
      setError(t('auth.organizationLabel'))
      return
    }

    setLoading(true)

    try {
      const role = createOrg ? 'ROLE_ORG_OWNER' : 'ROLE_USER'
      const body: Record<string, string | null> = {
        email,
        username,
        password,
        firstName: firstName || null,
        lastName: lastName || null,
        role,
      }

      if (createOrg) {
        body.organizationLabel = orgLabel
        body.organizationDomain = orgDomain || null
      }

      const res = await fetch(`${API_URL}/api/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      })

      if (!res.ok) {
        const data = await res.json()
        const code = data.code as string | undefined
        throw new Error(
          code && t(`errors.${code}`) !== `errors.${code}`
            ? t(`errors.${code}`)
            : data.detail || data.error || t('common.error'),
        )
      }

      setSuccess(true)
    } catch (err) {
      setError(err instanceof Error ? err.message : t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  if (success) {
    return (
      <Paper radius="md" p={isMobile ? 'md' : 'xl'} withBorder w="100%">
        <Alert color="green" variant="light" title={t('common.success')}>
          {t('auth.registrationSuccess')}
        </Alert>
        <Button fullWidth mt="lg" variant="light" onClick={() => navigate('/login')}>
          {t('auth.signIn')}
        </Button>
      </Paper>
    )
  }

  return (
    <Paper radius="md" p={isMobile ? 'md' : 'xl'} withBorder w="100%">
      <Title order={2} ta="center" mb={4}>
        {t('auth.createAccount')}
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb="lg">
        {t('auth.createAccountDesc')}
      </Text>

      <SsoButtons googleLabel={t('auth.ssoGoogle')} githubLabel={t('auth.ssoGithub')} />

      <Divider label={t('common.or')} labelPosition="center" my="lg" />

      <form onSubmit={handleSubmit}>
        <Stack>
          {error && (
            <Alert color="red" variant="light">
              {error}
            </Alert>
          )}

          <TextInput
            required
            label={t('auth.email')}
            placeholder="your@email.com"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.currentTarget.value)}
          />

          <TextInput
            required
            label={t('auth.username')}
            placeholder={t('auth.username')}
            value={username}
            onChange={(e) => setUsername(e.currentTarget.value)}
          />

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

          <PasswordInput
            required
            label={t('auth.password')}
            placeholder={t('auth.password')}
            value={password}
            onChange={(e) => setPassword(e.currentTarget.value)}
          />

          <PasswordInput
            required
            label={t('auth.confirmPassword')}
            placeholder={t('auth.confirmPassword')}
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.currentTarget.value)}
          />

          <Checkbox
            label={t('auth.createOrganization')}
            checked={createOrg}
            onChange={(e) => setCreateOrg(e.currentTarget.checked)}
          />

          <Collapse in={createOrg}>
            <Stack gap="sm">
              <TextInput
                required={createOrg}
                label={t('auth.organizationLabel')}
                placeholder={t('auth.organizationLabel')}
                value={orgLabel}
                onChange={(e) => setOrgLabel(e.currentTarget.value)}
              />
              <TextInput
                label={t('auth.organizationDomain')}
                placeholder="example.com"
                value={orgDomain}
                onChange={(e) => setOrgDomain(e.currentTarget.value)}
              />
            </Stack>
          </Collapse>

          <Button type="submit" fullWidth loading={loading}>
            {t('auth.signUp')}
          </Button>
        </Stack>
      </form>

      <Text c="dimmed" size="sm" ta="center" mt="md">
        {t('auth.hasAccount')}{' '}
        <Anchor component="button" type="button" fw={600} onClick={() => navigate('/login')}>
          {t('auth.signIn')}
        </Anchor>
      </Text>
    </Paper>
  )
}
