export type ToastType = 'success' | 'error' | 'info' | 'loading'

export type ToastMode = 'toast' | 'loading'

export interface ToastState {
  visible: boolean
  type: ToastType
  mode: ToastMode
  title: string
  subtitle: string
  shake: boolean
}

export interface ToastContextValue {
  showToast: (type: Exclude<ToastType, 'loading'>, title: string, subtitle?: string, duration?: number) => void
  startLoading: (title: string, subtitle?: string) => void
  finishLoading: (type: Exclude<ToastType, 'loading'>, title: string, subtitle?: string, duration?: number) => void
  closeToast: () => void
  isLoading: boolean
}
