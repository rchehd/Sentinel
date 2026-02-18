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
  SimpleGrid,
  Checkbox,
  Collapse,
} from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { SsoButtons } from './SsoButtons'
import { useToast } from '@/components/toast'
import { SentinelLogo } from '@/components/logo'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function RegisterForm() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const isMobile = useMediaQuery('(max-width: 480px)')
  const { showToast } = useToast()

  const [createOrg, setCreateOrg] = useState(false)
  const [email, setEmail] = useState('')
  const [username, setUsername] = useState('')
  const [firstName, setFirstName] = useState('')
  const [lastName, setLastName] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [orgLabel, setOrgLabel] = useState('')
  const [orgDomain, setOrgDomain] = useState('')
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})

  const clearError = (field: string) =>
    setErrors((prev) => (prev[field] ? { ...prev, [field]: '' } : prev))

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}
    if (!email) newErrors.email = t('auth.emailRequired')
    if (!username) newErrors.username = t('auth.usernameRequired')
    if (!password) newErrors.password = t('auth.passwordRequired')
    else if (password.length < 8) newErrors.password = t('auth.passwordMinLength')
    if (!confirmPassword) newErrors.confirmPassword = t('auth.confirmPasswordRequired')
    else if (password !== confirmPassword) newErrors.confirmPassword = t('auth.passwordsMustMatch')
    if (createOrg && !orgLabel.trim()) newErrors.orgLabel = t('auth.organizationRequired')
    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
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

      navigate('/register/check-email', { replace: true, state: { from: 'registration' } })
    } catch (err) {
      const message = err instanceof Error ? err.message : t('common.error')
      showToast('error', t('auth.registrationFailed'), message)
    } finally {
      setLoading(false)
    }
  }


  return (
    <Paper
      radius="md"
      p={isMobile ? 'md' : 'xl'}
      withBorder
      w="100%"
      className="theme-transition-slow"
    >
      <SentinelLogo size={48} />
      <Title order={2} ta="center" mb={4}>
        {t('auth.createAccount')}
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb="lg">
        {t('auth.createAccountDesc')}
      </Text>

      <SsoButtons googleLabel={t('auth.ssoGoogle')} githubLabel={t('auth.ssoGithub')} />

      <Divider label={t('common.or')} labelPosition="center" my="lg" />

      <form onSubmit={handleSubmit} noValidate>
        <Stack>
          <TextInput
            required
            label={t('auth.email')}
            placeholder="your@email.com"
            type="email"
            value={email}
            error={errors.email}
            onChange={(e) => { setEmail(e.currentTarget.value); clearError('email') }}
          />

          <TextInput
            required
            label={t('auth.username')}
            placeholder={t('auth.username')}
            value={username}
            error={errors.username}
            onChange={(e) => { setUsername(e.currentTarget.value); clearError('username') }}
          />

          <SimpleGrid cols={isMobile ? 1 : 2}>
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
          </SimpleGrid>

          <SimpleGrid cols={1}>
            <PasswordInput
              required
              label={t('auth.password')}
              placeholder={t('auth.password')}
              value={password}
              error={errors.password}
              onChange={(e) => { setPassword(e.currentTarget.value); clearError('password') }}
            />
            <PasswordInput
              required
              label={t('auth.confirmPassword')}
              placeholder={t('auth.confirmPassword')}
              value={confirmPassword}
              error={errors.confirmPassword}
              onChange={(e) => { setConfirmPassword(e.currentTarget.value); clearError('confirmPassword') }}
            />
          </SimpleGrid>

          <Checkbox
            label={t('auth.createOrganization')}
            checked={createOrg}
            onChange={(e) => setCreateOrg(e.currentTarget.checked)}
          />

          <Collapse in={createOrg}>
            <SimpleGrid cols={1}>
              <TextInput
                required={createOrg}
                label={t('auth.organizationLabel')}
                placeholder={t('auth.organizationLabel')}
                value={orgLabel}
                error={errors.orgLabel}
                onChange={(e) => { setOrgLabel(e.currentTarget.value); clearError('orgLabel') }}
              />
              <TextInput
                label={t('auth.organizationDomain')}
                placeholder="example.com"
                value={orgDomain}
                onChange={(e) => setOrgDomain(e.currentTarget.value)}
              />
            </SimpleGrid>
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
