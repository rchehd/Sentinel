import { useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Paper, Title, Text, Anchor, Stack } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { Mail } from 'lucide-react'
import { AuthLayout } from '@/components/auth'

export function RegisterCheckEmailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const state = location.state as { from?: string } | null
  const isMobile = useMediaQuery('(max-width: 480px)')

  useEffect(() => {
    if (!state?.from) {
      navigate('/register', { replace: true })
    }
  }, [state, navigate])

  if (!state?.from) return null

  return (
    <AuthLayout>
      <Paper radius="md" p={isMobile ? 'md' : 'xl'} withBorder w="100%" className="theme-transition-slow">
        <Stack ta="center" gap="md">
          <Mail size={48} style={{ margin: '0 auto' }} />
          <Title order={3}>{t('auth.checkEmail')}</Title>
          <Text c="dimmed" size="sm">
            {t('auth.checkEmailDesc')}
          </Text>
          <Anchor size="sm" onClick={() => navigate('/login')}>
            {t('auth.signIn')}
          </Anchor>
        </Stack>
      </Paper>
    </AuthLayout>
  )
}
