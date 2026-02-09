import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Center, Loader, Text } from '@mantine/core'
import { useTranslation } from 'react-i18next'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function ActivatePage() {
  const { token } = useParams<{ token: string }>()
  const navigate = useNavigate()
  const { t } = useTranslation()
  const [error, setError] = useState('')

  useEffect(() => {
    if (!token) return

    fetch(`${API_URL}/api/activate/${token}`)
      .then((res) => {
        if (!res.ok) throw new Error()
        navigate('/login?activated=true', { replace: true })
      })
      .catch(() => {
        setError(t('auth.activationFailed'))
      })
  }, [token, navigate, t])

  if (error) {
    return (
      <Center mih="100vh">
        <Text c="red" fw={500}>
          {error}
        </Text>
      </Center>
    )
  }

  return (
    <Center mih="100vh">
      <Loader size="lg" />
    </Center>
  )
}
