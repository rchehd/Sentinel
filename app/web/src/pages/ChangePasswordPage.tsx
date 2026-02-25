import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router-dom'
import { Paper, Title, Text, PasswordInput, Button, Stack, Alert } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { AuthLayout } from '@/components/auth'
import { useToast } from '@/components/toast'
import { SentinelLogo } from '@/components/logo'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function ChangePasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const isMobile = useMediaQuery('(max-width: 480px)')
  const { showToast } = useToast()

  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})

  const clearError = (field: string) =>
    setErrors((prev) => (prev[field] ? { ...prev, [field]: '' } : prev))

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const newErrors: Record<string, string> = {}
    if (!newPassword) newErrors.newPassword = t('auth.passwordRequired')
    else if (newPassword.length < 8) newErrors.newPassword = t('auth.passwordMinLength')
    if (!confirmPassword) newErrors.confirmPassword = t('auth.confirmPasswordRequired')
    else if (newPassword !== confirmPassword) newErrors.confirmPassword = t('auth.passwordsMustMatch')

    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
      return
    }

    setLoading(true)

    try {
      const res = await fetch(`${API_URL}/api/password/change`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ newPassword, confirmPassword }),
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

      showToast('success', t('auth.passwordChanged'), t('auth.changePasswordSuccess'))
      navigate('/home', { replace: true })
    } catch (err) {
      const message = err instanceof Error ? err.message : t('common.error')
      showToast('error', t('auth.changePasswordFailed'), message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout>
      <Paper
        radius="md"
        p={isMobile ? 'md' : 'xl'}
        withBorder
        w="100%"
        className="theme-transition-slow"
      >
        <SentinelLogo size={48} />
        <Title order={2} ta="center" mb={4}>
          {t('auth.changePassword')}
        </Title>
        <Text c="dimmed" size="sm" ta="center" mb="lg">
          {t('auth.changePasswordDesc')}
        </Text>

        <form onSubmit={handleSubmit} noValidate>
          <Stack>
            <PasswordInput
              required
              label={t('auth.newPassword')}
              placeholder={t('auth.newPassword')}
              value={newPassword}
              error={errors.newPassword}
              onChange={(e) => {
                setNewPassword(e.currentTarget.value)
                clearError('newPassword')
              }}
            />

            <PasswordInput
              required
              label={t('auth.confirmPassword')}
              placeholder={t('auth.confirmPassword')}
              value={confirmPassword}
              error={errors.confirmPassword}
              onChange={(e) => {
                setConfirmPassword(e.currentTarget.value)
                clearError('confirmPassword')
              }}
            />

            <Button type="submit" fullWidth loading={loading}>
              {t('auth.setPassword')}
            </Button>
          </Stack>
        </form>
      </Paper>
    </AuthLayout>
  )
}
