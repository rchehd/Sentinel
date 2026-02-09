import { useTranslation } from 'react-i18next'
import { useLocation, useNavigate } from 'react-router-dom'
import { NavLink, ScrollArea, Stack } from '@mantine/core'

export function Sidebar() {
  const { t } = useTranslation()
  const location = useLocation()
  const navigate = useNavigate()

  const links = [
    { label: t('nav.home'), href: '/home' },
    { label: t('nav.dashboard'), href: '/dashboard' },
    { label: t('nav.settings'), href: '/settings' },
    { label: t('nav.profile'), href: '/profile' },
  ]

  return (
    <ScrollArea p="md" style={{ flex: 1 }}>
      <Stack gap={4}>
        {links.map((link) => (
          <NavLink
            key={link.href}
            label={link.label}
            active={location.pathname === link.href}
            onClick={() => navigate(link.href)}
          />
        ))}
      </Stack>
    </ScrollArea>
  )
}
