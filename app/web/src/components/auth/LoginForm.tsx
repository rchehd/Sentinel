import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router-dom'
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
} from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { SsoButtons } from './SsoButtons'
import { useToast } from '@/components/toast'
import { SentinelLogo } from '@/components/logo'
import { useModeContext } from '@/context/ModeContext'
import { useAuth } from '@/context/AuthContext'
import { apiFetch } from '@/lib/api'

export function LoginForm() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const activation = searchParams.get('activation')
  const isMobile = useMediaQuery('(max-width: 480px)')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const { showToast } = useToast()
  const { mode } = useModeContext()
  const isSelfHosted = mode === 'self_hosted'

  const clearError = (field: string) =>
    setErrors((prev) => (prev[field] ? { ...prev, [field]: '' } : prev))

  const { setUser } = useAuth()

  useEffect(() => {
    if (activation === 'success') {
      showToast('success', t('auth.activationSuccess'), '', 5000)
    } else if (activation === 'already_activated') {
      showToast('info', t('auth.alreadyActivated'), t('auth.alreadyActivatedDesc'), 5000)
    } else if (activation === 'failed') {
      showToast('error', t('auth.activationFailed'), t('auth.activationFailedDesc'), 6000)
    }
  }, [activation, showToast, t])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}
    if (!email) newErrors.email = t('auth.emailRequired')
    if (!password) newErrors.password = t('auth.passwordRequired')
    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
      return
    }

    setLoading(true)

    try {
      const res = await apiFetch('/api/login', {
        method: 'POST',
        json: { email, password },
      })

      if (!res.ok) {
        const data = await res.json()
        const code = data.code as string | undefined
        throw new Error(
          code && t(`errors.${code}`) !== `errors.${code}`
            ? t(`errors.${code}`)
            : data.error || t('common.error'),
        )
      }

      const data = await res.json()
      setUser(data)

      if (data.mustChangePassword) {
        navigate('/change-password', { replace: true })
        return
      }

      showToast('success', t('auth.accessGranted'), t('auth.welcomeBack'))

      const wsRes = await apiFetch('/api/workspaces')
      const workspaces = wsRes.ok ? ((await wsRes.json()) as { slug: string }[]) : []
      const target = workspaces.length > 0 ? `/${workspaces[0].slug}` : '/change-password'
      navigate(target, { replace: true })
    } catch (err) {
      const message = err instanceof Error ? err.message : t('common.error')
      showToast('error', t('auth.authenticationFailed'), message)
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
        {t('auth.welcomeBack')}
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb={20}>
        {t('auth.welcomeBackDesc')}
      </Text>

      {!isSelfHosted && (
        <>
          <SsoButtons googleLabel={t('auth.ssoGoogle')} githubLabel={t('auth.ssoGithub')} />
          {import.meta.env.VITE_SSO_ENABLED && (
            <Divider label={t('common.or')} labelPosition="center" my="lg" />
          )}
        </>
      )}

      <form onSubmit={handleSubmit} noValidate>
        <Stack>
          <TextInput
            required
            label={t('auth.email')}
            placeholder="your@email.com"
            type="email"
            value={email}
            error={errors.email}
            onChange={(e) => {
              setEmail(e.currentTarget.value)
              clearError('email')
            }}
          />

          <div>
            <PasswordInput
              required
              label={t('auth.password')}
              placeholder={t('auth.password')}
              value={password}
              error={errors.password}
              onChange={(e) => {
                setPassword(e.currentTarget.value)
                clearError('password')
              }}
            />
            <Anchor component="button" type="button" size="xs" mt={4}>
              {t('auth.forgotPassword')}
            </Anchor>
          </div>

          <Button type="submit" fullWidth loading={loading}>
            {t('auth.signIn')}
          </Button>
        </Stack>
      </form>

      {!isSelfHosted && (
        <Text c="dimmed" size="sm" ta="center" mt="md">
          {t('auth.noAccount')}{' '}
          <Anchor component="button" type="button" fw={600} onClick={() => navigate('/register')}>
            {t('auth.signUp')}
          </Anchor>
        </Text>
      )}
    </Paper>
  )
}
