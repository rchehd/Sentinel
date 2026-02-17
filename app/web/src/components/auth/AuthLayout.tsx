import { useTranslation } from 'react-i18next'
import { Box, Group, Select, Anchor } from '@mantine/core'
import { useMediaQuery } from '@mantine/hooks'
import { supportedLanguages } from '@/i18n'
import { ThemeToggle } from '@/components/common'
import styles from './AuthLayout.module.scss'

interface AuthLayoutProps {
  children: React.ReactNode
}

export function AuthLayout({ children }: AuthLayoutProps) {
  const { t, i18n } = useTranslation()
  const isMobile = useMediaQuery('(max-width: 480px)')
  return (
    <Box className={styles.wrapper}>
      {/* Header */}
      <Group justify="right" p={isMobile ? 'xs' : 'md'} className={styles.header}>
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
      <Box w="100%" maw={440} mx="auto" px="md" py="xl" className={styles.main}>
        {children}
      </Box>

      {/* Footer */}
      <Group justify="center" gap="lg" pb="lg" className={styles.footer}>
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
