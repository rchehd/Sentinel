import { useTranslation } from 'react-i18next'
import { NavLink, ScrollArea, Stack } from '@mantine/core'

export function Sidebar() {
  const { t } = useTranslation()

  const links = [
    { label: t('nav.home'), href: '/' },
    { label: t('nav.dashboard'), href: '/dashboard' },
    { label: t('nav.settings'), href: '/settings' },
    { label: t('nav.profile'), href: '/profile' },
  ]

  return (
    <ScrollArea p="md" style={{ flex: 1 }}>
      <Stack gap={4}>
        {links.map((link) => (
          <NavLink key={link.href} label={link.label} href={link.href} />
        ))}
      </Stack>
    </ScrollArea>
  )
}
