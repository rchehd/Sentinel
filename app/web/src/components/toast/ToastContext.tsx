import { createContext, useCallback, useContext, useRef, useState } from 'react'
import type { ToastContextValue, ToastState, ToastType } from './types'
import { ToastPill } from './ToastPill'

const initialState: ToastState = {
    visible: false,
    type: 'info',
    mode: 'toast',
    title: '',
    subtitle: '',
    shake: false,
}

const ToastContext = createContext<ToastContextValue | null>(null)

export function ToastProvider({ children }: { children: React.ReactNode }) {
    const [state, setState] = useState<ToastState>(initialState)

    const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
    const shakeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
    const resetTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

    const clearTimers = useCallback(() => {
        if (hideTimerRef.current) clearTimeout(hideTimerRef.current)
        if (shakeTimerRef.current) clearTimeout(shakeTimerRef.current)
        if (resetTimerRef.current) clearTimeout(resetTimerRef.current)
    }, [])

    const hide = useCallback(() => {
        setState((prev) => ({ ...prev, visible: false }))
        resetTimerRef.current = setTimeout(() => {
            setState(initialState)
        }, 500)
    }, [])

    const scheduleHide = useCallback(
        (duration: number) => {
            if (hideTimerRef.current) clearTimeout(hideTimerRef.current)
            hideTimerRef.current = setTimeout(hide, duration)
        },
        [hide],
    )

    const showToast = useCallback(
        (type: Exclude<ToastType, 'loading'>, title: string, subtitle = '', duration = 5000) => {
            clearTimers()

            const isError = type === 'error'
            setState({
                visible: true,
                type,
                mode: 'toast',
                title,
                subtitle,
                shake: isError,
            })

            if (isError) {
                shakeTimerRef.current = setTimeout(() => {
                    setState((prev) => ({ ...prev, shake: false }))
                }, 500)
            }

            if (duration > 0) {
                scheduleHide(duration)
            }
        },
        [clearTimers, scheduleHide],
    )

    const startLoading = useCallback(
        (title: string, subtitle = '') => {
            clearTimers()
            setState({
                visible: true,
                type: 'loading',
                mode: 'loading',
                title,
                subtitle,
                shake: false,
            })
        },
        [clearTimers],
    )

    const finishLoading = useCallback(
        (type: Exclude<ToastType, 'loading'>, title: string, subtitle = '', duration = 3000) => {
            clearTimers()

            const isError = type === 'error'
            setState({
                visible: true,
                type,
                mode: 'loading',
                title,
                subtitle,
                shake: isError,
            })

            if (isError) {
                shakeTimerRef.current = setTimeout(() => {
                    setState((prev) => ({ ...prev, shake: false }))
                }, 500)
            }

            if (duration > 0) {
                scheduleHide(duration)
            }
        },
        [clearTimers, scheduleHide],
    )

    const closeToast = useCallback(() => {
        clearTimers()
        hide()
    }, [clearTimers, hide])

    const handleMouseEnter = useCallback(() => {
        if (state.mode === 'toast' && hideTimerRef.current) {
            clearTimeout(hideTimerRef.current)
        }
    }, [state.mode])

    const handleMouseLeave = useCallback(() => {
        if (state.mode === 'toast' && state.visible) {
            scheduleHide(3000)
        }
    }, [state.mode, state.visible, scheduleHide])

    const value: ToastContextValue = {
        showToast,
        startLoading,
        finishLoading,
        closeToast,
        isLoading: state.type === 'loading' && state.visible,
    }

    return (
        <ToastContext.Provider value={value}>
            {children}
            <ToastPill
                state={state}
                onClose={closeToast}
                onMouseEnter={handleMouseEnter}
                onMouseLeave={handleMouseLeave}
            />
        </ToastContext.Provider>
    )
}

export function useToast(): ToastContextValue {
    const context = useContext(ToastContext)
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider')
    }
    return context
}