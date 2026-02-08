import { useTranslation } from 'react-i18next'
import { Title, Text } from '@mantine/core'
import { AppLayout } from '@/components/layout'

function App() {
  const { t } = useTranslation()

  return (
    <AppLayout>
      <Title order={2}>{t('common.welcome')}</Title>
      <Text mt="md" c="dimmed">
        {t('nav.dashboard')}
      </Text>
    </AppLayout>
  )
}

export default App
