import { cn } from '@/lib/utils'
import { OrbitalLogo } from './OrbitalLogo'
import styles from './PageLoader.module.scss'

interface PageLoaderProps {
  variant?: 'full' | 'route'
  visible: boolean
  text?: string
}

/**
 * Full-screen or route-level loading overlay with animated orbital logo.
 *
 * `visible` drives a CSS opacity/visibility transition rather than mounting
 * or unmounting the element. This is intentional: if the element were removed
 * from the DOM when hidden, any ongoing CSS fade-out would be cut short and
 * sibling elements (e.g. a Suspense spinner) could bleed through.
 *
 * The text node is always rendered (even when `text` is undefined) so that the
 * DOM element participates in the parent's opacity transition. Conditionally
 * rendering it would cause the text to disappear instantly while the overlay
 * is still mid-fade.
 */
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
      <div className={styles.text}>{text}</div>
    </div>
  )
}
