import { useEffect, useRef } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Center, Loader } from '@mantine/core'

const API_URL = import.meta.env.VITE_API_URL || 'https://api.sentinel.localhost'

export function ActivatePage() {
  const { token } = useParams<{ token: string }>()
  const navigate = useNavigate()
  // Prevents double API call in React StrictMode (effects run twice in dev).
  // The activation token is one-time-use, so a second fetch would see 409.
  const didFetch = useRef(false)

  useEffect(() => {
    if (!token || didFetch.current) return
    didFetch.current = true

    fetch(`${API_URL}/api/activate/${token}`)
      .then(async (res) => {
        if (res.status === 409) {
          navigate('/login?activation=already_activated', { replace: true })
          return
        }
        if (!res.ok) throw new Error()
        navigate('/login?activation=success', { replace: true })
      })
      .catch(() => {
        navigate('/login?activation=failed', { replace: true })
      })
  }, [token, navigate])

  return (
    <Center mih="100vh">
      <Loader size="lg" />
    </Center>
  )
}
