import { useTranslation } from 'react-i18next'
import {
  AppShell,
  Burger,
  Group,
  Title,
  Select,
  ActionIcon,
  useMantineColorScheme,
} from '@mantine/core'
import { useDisclosure } from '@mantine/hooks'
import { supportedLanguages } from '@/i18n'
import { Sidebar } from './Sidebar'

interface AppLayoutProps {
  children: React.ReactNode
}

export function AppLayout({ children }: AppLayoutProps) {
  const { t, i18n } = useTranslation()
  const [opened, { toggle }] = useDisclosure(true)
  const { colorScheme, toggleColorScheme } = useMantineColorScheme()

  return (
    <AppShell
      header={{ height: 60 }}
      navbar={{
        width: 260,
        breakpoint: 'sm',
        collapsed: { mobile: !opened, desktop: !opened },
      }}
      padding="md"
    >
      <AppShell.Header>
        <Group h="100%" px="md" justify="space-between">
          <Group>
            <Burger opened={opened} onClick={toggle} size="sm" />
            <Title order={3}>Sentinel</Title>
          </Group>

          <Group>
            <Select
              size="xs"
              w={140}
              data={supportedLanguages.map((lang) => ({
                value: lang.code,
                label: lang.label,
              }))}
              value={i18n.language}
              onChange={(value) => value && i18n.changeLanguage(value)}
              allowDeselect={false}
              aria-label={t('common.language')}
            />

            <ActionIcon
              variant="default"
              size="lg"
              onClick={toggleColorScheme}
              aria-label="Toggle color scheme"
            >
              {colorScheme === 'dark' ? '☀️' : '🌙'}
            </ActionIcon>
          </Group>
        </Group>
      </AppShell.Header>

      <AppShell.Navbar>
        <Sidebar />
      </AppShell.Navbar>

      <AppShell.Main>{children}</AppShell.Main>
    </AppShell>
  )
}
