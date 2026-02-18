import {
  createTheme,
  defaultVariantColorsResolver,
  defaultCssVariablesResolver,
  type MantineTheme,
} from '@mantine/core'

const themeTransition = 'background-color 0.5s ease, color 0.5s ease, border-color 0.5s ease'

// Tailwind Slate palette — matches the design prototype
const slate = {
  50:  '#f8fafc',
  100: '#f1f5f9',
  200: '#e2e8f0',
  300: '#cbd5e1',
  400: '#94a3b8',
  500: '#64748b',
  600: '#475569',
  700: '#334155',
  800: '#1e293b',
  900: '#0f172a',
  950: '#020617',
}

// Passed directly to <MantineProvider cssVariablesResolver={...}>
export const cssVariablesResolver = (theme: MantineTheme) => {
  const defaults = defaultCssVariablesResolver(theme)
  return {
    ...defaults,
    light: {
      ...defaults.light,
      '--mantine-color-body':           slate[50],   // page background
      '--mantine-color-text':           slate[900],
      '--mantine-color-dimmed':         slate[500],
      '--mantine-color-default':        '#ffffff',   // input / default element bg
      '--mantine-color-default-hover':  slate[100],
      '--mantine-color-default-border': slate[200],  // borders + auth dot grid
      '--mantine-color-default-color':  slate[900],
      '--mantine-color-placeholder':    slate[400],
      '--mantine-color-error':          '#ef4444',
      '--mantine-color-dark-filled':       slate[900],
      '--mantine-color-dark-filled-hover': slate[900],  // no color change on hover
    },
    dark: {
      ...defaults.dark,
      '--mantine-color-body':           slate[950],  // page background
      '--mantine-color-text':           slate[50],
      '--mantine-color-dimmed':         slate[400],
      '--mantine-color-default':        slate[800],  // input / default element bg
      '--mantine-color-default-hover':  slate[700],
      '--mantine-color-default-border': slate[800],  // borders + auth dot grid
      '--mantine-color-default-color':  slate[50],
      '--mantine-color-placeholder':    slate[500],
      '--mantine-color-error':          '#f87171',
      // Buttons without an explicit variant prop fall back to these CSS vars
      '--mantine-color-dark-filled':       '#ffffff',
      '--mantine-color-dark-filled-hover': '#ffffff',  // no color change — opacity+lift via CSS
    },
  }
}

export const theme = createTheme({
  fontFamily: 'Inter, sans-serif',
  fontFamilyMonospace: 'JetBrains Mono, monospace',
  defaultRadius: 'md',
  primaryColor: 'dark',

  colors: {
    dark: [
      slate[100], // 0
      slate[200], // 1
      slate[300], // 2
      slate[400], // 3
      slate[500], // 4
      slate[600], // 5
      slate[700], // 6
      slate[800], // 7
      slate[900], // 8
      slate[950], // 9
    ],
  },

  variantColorResolver: (input) => {
    const defaultColors = defaultVariantColorsResolver(input)

    if (input.variant === 'filled') {
      return {
        ...defaultColors,
        background: `light-dark(${slate[900]}, #ffffff)`,
        hover:      `light-dark(${slate[900]}, #ffffff)`,  // no color change — opacity+lift via CSS
        color:      `light-dark(#ffffff, ${slate[900]})`,
      }
    }

    return defaultColors
  },

  components: {
    Paper: {
      defaultProps: {
        shadow: 'xl',
      },
      styles: {
        root: {
          // white card on Slate-50 bg (light) · Slate-900 card on Slate-950 bg (dark)
          backgroundColor: `light-dark(#ffffff, ${slate[900]})`,
          transition: themeTransition,
        },
      },
    },
    AppShell: {
      styles: {
        main:   { transition: themeTransition },
        header: { transition: themeTransition },
        navbar: { transition: themeTransition },
      },
    },
    TextInput: {
      styles: {
        root:  { transition: themeTransition },
        input: { transition: themeTransition },
        label: { transition: themeTransition },
        error: { transition: themeTransition },
      },
    },
    PasswordInput: {
      styles: {
        root:       { transition: themeTransition },
        innerInput: { transition: themeTransition },
        input:      { transition: themeTransition },
        label:      { transition: themeTransition },
        error:      { transition: themeTransition },
      },
    },
    Select: {
      styles: {
        root:  { transition: themeTransition },
        input: { transition: themeTransition },
        label: { transition: themeTransition },
      },
    },
    Checkbox: {
      styles: {
        label: { transition: themeTransition },
        input: {
          transition: themeTransition,
          borderColor: `light-dark(${slate[200]}, ${slate[600]})`,
        },
      },
    },
    Button: {
      styles: {
        root: { transition: themeTransition },
      },
    },
    Divider: {
      styles: {
        root: { transition: themeTransition },
      },
    },
    Anchor: {
      styles: {
        root: { transition: themeTransition },
      },
    },
  },
})
