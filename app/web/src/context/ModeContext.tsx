import { createContext, useContext } from 'react'

export type AppMode = 'saas' | 'self_hosted'

interface ModeContextType {
  mode: AppMode | null
}

export const ModeContext = createContext<ModeContextType>({ mode: null })

export function useModeContext(): ModeContextType {
  return useContext(ModeContext)
}
