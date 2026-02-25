import { useState } from 'react'
import { Paper, Title, Text, TextInput, PasswordInput, Button, Stack } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { AuthLayout } from '@/components/auth'
import { useToast } from '@/components/toast'
import { SentinelLogo } from '@/components/logo'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

function SetupForm() {
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
    if (!email) newErrors.email = 'Email is required.'
    if (!username) newErrors.username = 'Username is required.'
    if (!password) newErrors.password = 'Password is required.'
    else if (password.length < 8) newErrors.password = 'Password must be at least 8 characters.'
    if (!confirmPassword) newErrors.confirmPassword = 'Please confirm your password.'
    else if (password !== confirmPassword) newErrors.confirmPassword = 'Passwords do not match.'
    if (Object.keys(newErrors).length) {
      setErrors(newErrors)
      return
    }

    setLoading(true)

    try {
      const res = await fetch(`${API_URL}/api/setup/admin`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, username, password }),
      })

      if (res.status === 403) {
        showToast('info', 'Already configured', 'An admin account already exists.')
        window.location.replace('/login')
        return
      }

      if (!res.ok) {
        const data = await res.json()
        throw new Error(data.detail || data.error || 'Setup failed.')
      }

      showToast('success', 'Admin account created', 'You can now log in.')
      window.location.replace('/login')
    } catch (err) {
      const message = err instanceof Error ? err.message : 'An unexpected error occurred.'
      showToast('error', 'Setup failed', message)
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
        Create admin account
      </Title>
      <Text c="dimmed" size="sm" ta="center" mb="lg">
        Set up the first administrator account to get started.
      </Text>

      <form onSubmit={handleSubmit} noValidate>
        <Stack>
          <TextInput
            required
            label="Email"
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
            label="Username"
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
            label="Password"
            placeholder="Min. 8 characters"
            value={password}
            error={errors.password}
            onChange={(e) => {
              setPassword(e.currentTarget.value)
              clearError('password')
            }}
          />

          <PasswordInput
            required
            label="Confirm password"
            placeholder="Repeat your password"
            value={confirmPassword}
            error={errors.confirmPassword}
            onChange={(e) => {
              setConfirmPassword(e.currentTarget.value)
              clearError('confirmPassword')
            }}
          />

          <Button type="submit" fullWidth loading={loading}>
            Create admin account
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
