import { useTranslation } from 'react-i18next'
import { Title, Text, SimpleGrid, Paper, ThemeIcon, Group } from '@mantine/core'
import { AppLayout } from '@/components/layout'

export function HomePage() {
  const { t } = useTranslation()

  return (
    <AppLayout>
      <Title order={2} mb="sm">
        {t('common.welcome')}
      </Title>
      <Text c="dimmed" mb="xl">
        {t('home.description')}
      </Text>

      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
        <StatCard emoji="📝" label={t('home.forms')} value="0" />
        <StatCard emoji="📨" label={t('home.submissions')} value="0" />
        <StatCard emoji="⚙️" label={t('home.workflows')} value="0" />
      </SimpleGrid>
    </AppLayout>
  )
}

function StatCard({ emoji, label, value }: { emoji: string; label: string; value: string }) {
  return (
    <Paper withBorder p="md" radius="md">
      <Group>
        <ThemeIcon variant="light" size="lg" radius="md">
          {emoji}
        </ThemeIcon>
        <div>
          <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
            {label}
          </Text>
          <Text fw={700} size="xl">
            {value}
          </Text>
        </div>
      </Group>
    </Paper>
  )
}
