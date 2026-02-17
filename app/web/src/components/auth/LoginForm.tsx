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

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function LoginForm() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const activated = searchParams.get('activated') === 'true'
  const isMobile = useMediaQuery('(max-width: 480px)')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const { showToast } = useToast()

  useEffect(() => {
    if (activated) {
      showToast('success', t('auth.activationSuccess'), '', 5000)
    }
  }, [activated, showToast, t])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)

    try {
      const res = await fetch(`${API_URL}/api/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
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

      showToast('success', t('auth.accessGranted'), t('auth.welcomeBack'))
      setTimeout(() => navigate('/home', { replace: true }), 1500)
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
        Sentinel
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb={8}>
        {t('auth.welcomeBackDesc')}
      </Text>

      <SsoButtons googleLabel={t('auth.ssoGoogle')} githubLabel={t('auth.ssoGithub')} />

      <Divider label={t('common.or')} labelPosition="center" my="lg" />

      <form onSubmit={handleSubmit}>
        <Stack>
          <TextInput
            required
            label={t('auth.email')}
            placeholder="your@email.com"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.currentTarget.value)}
          />

          <div>
            <PasswordInput
              required
              label={t('auth.password')}
              placeholder={t('auth.password')}
              value={password}
              onChange={(e) => setPassword(e.currentTarget.value)}
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

      <Text c="dimmed" size="sm" ta="center" mt="md">
        {t('auth.noAccount')}{' '}
        <Anchor component="button" type="button" fw={600} onClick={() => navigate('/register')}>
          {t('auth.signUp')}
        </Anchor>
      </Text>
    </Paper>
  )
}
