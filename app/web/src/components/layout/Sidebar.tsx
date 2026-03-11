import { useTranslation } from 'react-i18next'
import { useLocation, useNavigate } from 'react-router-dom'
import { NavLink, ScrollArea, Stack } from '@mantine/core'
import { useWorkspace } from '@/context/WorkspaceContext'

export function Sidebar() {
  const { t } = useTranslation()
  const location = useLocation()
  const navigate = useNavigate()
  const workspaceCtx = useWorkspace()

  if (!workspaceCtx) return null

  const base = `/${workspaceCtx.workspace.slug}`

  const links = [
    { label: t('nav.home'), href: `${base}/home` },
    { label: t('nav.dashboard'), href: `${base}/dashboard` },
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
