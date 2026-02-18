import { createTheme, defaultVariantColorsResolver } from '@mantine/core'

const themeTransition = 'background-color 0.5s ease, color 0.5s ease, border-color 0.5s ease'

export const theme = createTheme({
  fontFamily: 'Inter, sans-serif',
  fontFamilyMonospace: 'JetBrains Mono, monospace',
  defaultRadius: 'md',
  primaryColor: 'dark',
  colors: {
    dark: [
      '#C9C9C9', // 0
      '#b8b8b8', // 1
      '#828282', // 2
      '#696969', // 3
      '#424242', // 4
      '#3b3b3b', // 5
      '#2e2e2e', // 6
      '#1e1e1e', // 7
      '#141414', // 8
      '#0f172a', // 9 - Slate 900 (matches sample --primary)
    ],
  },
  variantColorResolver: (input) => {
    const defaultColors = defaultVariantColorsResolver(input)

    if (input.variant === 'filled') {
      return {
        ...defaultColors,
        background: 'light-dark(var(--mantine-color-dark-8), #ffffff)',
        hover: 'light-dark(var(--mantine-color-dark-7), #f0f0f0)',
        color: 'light-dark(#ffffff, #1e1e1e)',
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
          transition: themeTransition,
        },
      },
    },
    AppShell: {
      styles: {
        main: { transition: themeTransition },
        header: { transition: themeTransition },
        navbar: { transition: themeTransition },
      },
    },
    TextInput: {
      styles: {
        root: { transition: themeTransition },
        input: { transition: themeTransition },
        label: { transition: themeTransition },
      },
    },
    PasswordInput: {
      styles: {
        root: { transition: themeTransition },
        innerInput: { transition: themeTransition },
        input: { transition: themeTransition },
        label: { transition: themeTransition },
      },
    },
    Select: {
      styles: {
        root: { transition: themeTransition },
        input: { transition: themeTransition },
        label: { transition: themeTransition },
      },
    },
    Checkbox: {
      styles: {
        label: { transition: themeTransition },
      },
    },
    Button: {
      styles: {
        root: {
          transition: `${themeTransition}, opacity 0.2s ease, transform 0.2s ease`,
        },
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
