import { cn } from '@/lib/utils'
import { OrbitalLogo } from './OrbitalLogo'
import styles from './PageLoader.module.scss'

interface PageLoaderProps {
  variant?: 'full' | 'route'
  visible: boolean
  text?: string
}

export function PageLoader({ variant = 'full', visible, text }: PageLoaderProps) {
  return (
    <div
      role="status"
      aria-label={text ?? 'Loading'}
      className={cn(
        styles.overlay,
        variant === 'full' ? styles.full : styles.route,
        visible ? styles.visible : styles.hidden,
      )}
    >
      <OrbitalLogo />
      {text && <div className={styles.text}>{text}</div>}
    </div>
  )
}
