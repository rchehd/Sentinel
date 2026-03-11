import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Paper, Title, Text, TextInput, PasswordInput, Button, Stack } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { AuthLayout } from '@/components/auth'
import { useToast } from '@/components/toast'
import { SentinelLogo } from '@/components/logo'
import { apiFetch } from '@/lib/api'

function SetupForm() {
  const { t } = useTranslation()
  const isMobile = useMediaQuery('(max-width: 480px)')
  const { showToast } = useToast()

  const [email, setEmail] = useState('')
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
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
    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
      return
    }

    setLoading(true)

    try {
      const res = await apiFetch('/api/setup/admin', {
        method: 'POST',
        json: { email, username, password },
      })

      if (res.status === 403) {
        showToast('info', t('setup.alreadyConfigured'), t('setup.alreadyConfiguredDesc'))
        window.location.replace('/login')
        return
      }

      if (!res.ok) {
        const data = await res.json()
        throw new Error(data.detail || data.error || t('setup.failed'))
      }

      showToast('success', t('setup.title'), t('setup.successDesc'))
      window.location.replace('/login')
    } catch (err) {
      showToast('error', t('setup.failed'), err instanceof Error ? err.message : t('common.error'))
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
        {t('setup.title')}
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb="lg">
        {t('setup.description')}
      </Text>

      <form onSubmit={handleSubmit} noValidate>
        <Stack>
          <TextInput
            required
            label={t('auth.email')}
            placeholder="admin@example.com"
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
            placeholder="admin"
            value={username}
            error={errors.username}
            onChange={(e) => {
              setUsername(e.currentTarget.value)
              clearError('username')
            }}
          />

          <PasswordInput
            required
            label={t('auth.password')}
            placeholder={t('auth.passwordMinLength')}
            value={password}
            error={errors.password}
            onChange={(e) => {
              setPassword(e.currentTarget.value)
              clearError('password')
            }}
          />

          <PasswordInput
            required
            label={t('auth.confirmPassword')}
            placeholder={t('setup.repeatPassword')}
            value={confirmPassword}
            error={errors.confirmPassword}
            onChange={(e) => {
              setConfirmPassword(e.currentTarget.value)
              clearError('confirmPassword')
            }}
          />

          <Button type="submit" fullWidth loading={loading}>
            {t('setup.createAdminAccount')}
          </Button>
        </Stack>
      </form>
    </Paper>
  )
}

export function SetupPage() {
  return (
    <AuthLayout>
      <SetupForm />
    </AuthLayout>
  )
}
