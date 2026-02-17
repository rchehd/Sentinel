import { createPortal } from 'react-dom'
import { cn } from '@/lib/utils'
import type { ToastState } from './types'
import styles from './ToastPill.module.scss'

interface ToastPillProps {
  state: ToastState
  onClose: () => void
  onMouseEnter: () => void
  onMouseLeave: () => void
}

export function ToastPill({ state, onClose, onMouseEnter, onMouseLeave }: ToastPillProps) {
  const { visible, type, mode, title, subtitle, shake } = state

  return createPortal(
    <div
      role="alert"
      aria-live="assertive"
      onMouseEnter={onMouseEnter}
      onMouseLeave={onMouseLeave}
      className={cn(
        styles.pill,
        visible && styles.visible,
        shake && styles.shake,
        type === 'success' && styles.success,
        type === 'error' && styles.error,
        type === 'info' && styles.info,
      )}
    >
      {/* Icon area */}
      <div className={styles.iconWrapper}>
        {/* Spinner */}
        <div
          className={styles.spinner}
          data-active={type === 'loading'}
        />
        {/* Success */}
        <svg
          className={cn(styles.icon, styles.iconSuccess)}
          data-active={type === 'success'}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth="2.5"
        >
          <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        {/* Error */}
        <svg
          className={cn(styles.icon, styles.iconError)}
          data-active={type === 'error'}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth="2.5"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        {/* Info */}
        <svg
          className={cn(styles.icon, styles.iconInfo)}
          data-active={type === 'info'}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth="2.5"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>

      {/* Content */}
      <div className={styles.content}>
        <span className={styles.title}>{title}</span>
        {subtitle && <span className={styles.subtitle}>{subtitle}</span>}
      </div>

      {/* Close button — only in toast mode */}
      {mode === 'toast' && (
        <button
          className={styles.close}
          onClick={onClose}
          title="Dismiss"
          aria-label="Dismiss notification"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 14 14"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M13 1L1 13M1 1l12 12" />
          </svg>
        </button>
      )}
    </div>,
    document.body,
  )
}
