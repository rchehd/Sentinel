import { useTranslation } from 'react-i18next'
import { Box, Group, Select, Anchor } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { supportedLanguages } from '@/i18n'
import { ThemeToggle } from '@/components/common'

interface AuthLayoutProps {
  children: React.ReactNode
}

export function AuthLayout({ children }: AuthLayoutProps) {
  const { t, i18n } = useTranslation()
  const isMobile = useMediaQuery('(max-width: 480px)')

  return (
    <Box
      style={{
        minHeight: '100vh',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden',
        backgroundImage:
          'radial-gradient(var(--mantine-color-default-border) 1px, transparent 1px)',
        backgroundSize: '24px 24px',
        transition: 'background-color 0.5s ease, color 0.5s ease',
      }}
    >
      {/* Header */}
      <Group justify="right" p={isMobile ? 'xs' : 'md'} style={{ position: 'relative', zIndex: 10 }}>
        <Group gap="xs">
          <Select
            size="xs"
            w={130}
            data={supportedLanguages.map((lang) => ({
              value: lang.code,
              label: lang.label,
            }))}
            value={i18n.language}
            onChange={(value) => value && i18n.changeLanguage(value)}
            allowDeselect={false}
            aria-label={t('common.language')}
          />
          <ThemeToggle />
        </Group>
      </Group>

      {/* Main */}
      <Box
        w="100%"
        maw={440}
        mx="auto"
        px="md"
        py="sm"
        style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}
      >
        <div className="auth-fade-in">{children}</div>
      </Box>

      {/* Footer */}
      <Group justify="center" gap="lg" pb="lg" style={{ position: 'relative', zIndex: 10 }}>
        <Anchor size="xs" c="dimmed" href="#">
          {t('footer.privacy')}
        </Anchor>
        <Anchor size="xs" c="dimmed" href="#">
          {t('footer.terms')}
        </Anchor>
        <Anchor size="xs" c="dimmed" href="#">
          {t('footer.support')}
        </Anchor>
      </Group>
    </Box>
  )
}
