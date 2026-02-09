import { useState } from 'react'
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
  Alert,
} from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { SsoButtons } from './SsoButtons'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function LoginForm() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const activated = searchParams.get('activated') === 'true'
  const isMobile = useMediaQuery('(max-width: 480px)')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
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
        throw new Error(code && t(`errors.${code}`) !== `errors.${code}` ? t(`errors.${code}`) : data.error || t('common.error'))
      }

      navigate('/home', { replace: true })
    } catch (err) {
      setError(err instanceof Error ? err.message : t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <Paper radius="md" p={isMobile ? 'md' : 'xl'} withBorder w="100%">
      <Title order={2} ta="center" mb={4}>
        {t('auth.welcomeBack')}
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb="lg">
        {t('auth.welcomeBackDesc')}
      </Text>

      <SsoButtons googleLabel={t('auth.ssoGoogle')} githubLabel={t('auth.ssoGithub')} />

      <Divider label={t('common.or')} labelPosition="center" my="lg" />

      <form onSubmit={handleSubmit}>
        <Stack>
          {activated && (
            <Alert color="green" variant="light">
              {t('auth.activationSuccess')}
            </Alert>
          )}

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
