import {
  alpha,
  createTheme,
  responsiveFontSizes,
} from '@mui/material/styles';

const colours = {
  ink: '#0B1220',
  inkSoft: '#344054',
  muted: '#667085',
  canvas: '#F4F7FB',
  surface: '#FFFFFF',
  primary: '#246BFD',
  primaryDark: '#174EA6',
  primaryLight: '#6EA8FE',
  secondary: '#0891B2',
  success: '#079455',
  warning: '#DC6803',
  error: '#D92D20',
  info: '#1570EF',
};

const baseTheme = createTheme({
  palette: {
    mode: 'light',

    primary: {
      main: colours.primary,
      dark: colours.primaryDark,
      light: colours.primaryLight,
      contrastText: '#FFFFFF',
    },

    secondary: {
      main: colours.secondary,
      dark: '#0E7490',
      light: '#67E8F9',
      contrastText: '#FFFFFF',
    },

    success: {
      main: colours.success,
      dark: '#067647',
      light: '#47CD89',
    },

    warning: {
      main: colours.warning,
      dark: '#B54708',
      light: '#FDB022',
    },

    error: {
      main: colours.error,
      dark: '#B42318',
      light: '#F97066',
    },

    info: {
      main: colours.info,
      dark: '#175CD3',
      light: '#53B1FD',
    },

    background: {
      default: colours.canvas,
      paper: colours.surface,
    },

    text: {
      primary: colours.ink,
      secondary: colours.muted,
    },

    divider: alpha(colours.ink, 0.08),

    action: {
      active: colours.inkSoft,
      hover: alpha(colours.primary, 0.06),
      selected: alpha(colours.primary, 0.1),
      disabled: alpha(colours.ink, 0.28),
      disabledBackground: alpha(colours.ink, 0.06),
      focus: alpha(colours.primary, 0.16),
    },
  },

  shape: {
    borderRadius: 14,
  },

  spacing: 8,

  typography: {
    fontFamily: [
      'Inter',
      'Segoe UI Variable',
      'Segoe UI',
      'SF Pro Display',
      'Roboto',
      'Helvetica Neue',
      'Arial',
      'sans-serif',
    ].join(','),

    h1: {
      fontSize: '3rem',
      lineHeight: 1.08,
      fontWeight: 750,
      letterSpacing: '-0.045em',
    },

    h2: {
      fontSize: '2.25rem',
      lineHeight: 1.12,
      fontWeight: 740,
      letterSpacing: '-0.035em',
    },

    h3: {
      fontSize: '1.875rem',
      lineHeight: 1.18,
      fontWeight: 720,
      letterSpacing: '-0.025em',
    },

    h4: {
      fontSize: '1.5rem',
      lineHeight: 1.25,
      fontWeight: 700,
      letterSpacing: '-0.018em',
    },

    h5: {
      fontSize: '1.25rem',
      lineHeight: 1.35,
      fontWeight: 680,
      letterSpacing: '-0.012em',
    },

    h6: {
      fontSize: '1rem',
      lineHeight: 1.45,
      fontWeight: 680,
    },

    subtitle1: {
      fontSize: '1rem',
      lineHeight: 1.5,
      fontWeight: 620,
    },

    subtitle2: {
      fontSize: '0.875rem',
      lineHeight: 1.5,
      fontWeight: 640,
    },

    body1: {
      fontSize: '0.975rem',
      lineHeight: 1.65,
      fontWeight: 400,
    },

    body2: {
      fontSize: '0.875rem',
      lineHeight: 1.6,
      fontWeight: 400,
    },

    caption: {
      fontSize: '0.75rem',
      lineHeight: 1.5,
      fontWeight: 500,
      letterSpacing: '0.01em',
    },

    button: {
      fontSize: '0.875rem',
      lineHeight: 1,
      fontWeight: 650,
      letterSpacing: '-0.005em',
      textTransform: 'none',
    },

    overline: {
      fontSize: '0.7rem',
      lineHeight: 1.5,
      fontWeight: 700,
      letterSpacing: '0.09em',
      textTransform: 'uppercase',
    },
  },

  transitions: {
    duration: {
      shortest: 100,
      shorter: 150,
      short: 180,
      standard: 240,
      complex: 320,
      enteringScreen: 240,
      leavingScreen: 180,
    },
  },

  components: {
    MuiCssBaseline: {
      styleOverrides: {
        html: {
          minHeight: '100%',
          backgroundColor: colours.canvas,
        },

        body: {
          minWidth: 320,
          minHeight: '100%',
          margin: 0,
          color: colours.ink,
          backgroundColor: colours.canvas,
          textRendering: 'optimizeLegibility',
          WebkitFontSmoothing: 'antialiased',
          MozOsxFontSmoothing: 'grayscale',
        },

        '#root': {
          minHeight: '100vh',
        },

        '*': {
          boxSizing: 'border-box',
          scrollbarWidth: 'thin',
          scrollbarColor: `${alpha(colours.ink, 0.18)} transparent`,
        },

        '*::-webkit-scrollbar': {
          width: 8,
          height: 8,
        },

        '*::-webkit-scrollbar-track': {
          background: 'transparent',
        },

        '*::-webkit-scrollbar-thumb': {
          borderRadius: 999,
          backgroundColor: alpha(colours.ink, 0.16),
        },

        '*::-webkit-scrollbar-thumb:hover': {
          backgroundColor: alpha(colours.ink, 0.24),
        },

        '::selection': {
          color: colours.primaryDark,
          backgroundColor: alpha(colours.primary, 0.18),
        },
      },
    },

    MuiButton: {
      defaultProps: {
        disableElevation: true,
      },

      styleOverrides: {
        root: {
          minHeight: 42,
          borderRadius: 11,
          paddingInline: 18,
          transition:
            'transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease',

          '&:active': {
            transform: 'translateY(1px)',
          },

          '&.MuiButton-containedPrimary': {
            boxShadow: `0 8px 20px ${alpha(
              colours.primary,
              0.2
            )}`,

            '&:hover': {
              boxShadow: `0 12px 26px ${alpha(
                colours.primary,
                0.28
              )}`,
            },
          },

          '&.MuiButton-outlined': {
            borderColor: alpha(colours.ink, 0.14),

            '&:hover': {
              borderColor: alpha(colours.primary, 0.42),
              backgroundColor: alpha(colours.primary, 0.04),
            },
          },
        },

        sizeLarge: {
          minHeight: 48,
          paddingInline: 22,
          borderRadius: 12,
        },

        sizeSmall: {
          minHeight: 34,
          paddingInline: 13,
          borderRadius: 9,
        },
      },
    },

    MuiIconButton: {
      styleOverrides: {
        root: {
          borderRadius: 11,
          transition:
            'background-color 160ms ease, transform 160ms ease',

          '&:active': {
            transform: 'scale(0.97)',
          },
        },
      },
    },

    MuiPaper: {
      defaultProps: {
        elevation: 0,
      },

      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },

    MuiCard: {
      defaultProps: {
        elevation: 0,
      },

      styleOverrides: {
        root: {
          border: `1px solid ${alpha(colours.ink, 0.075)}`,
          borderRadius: 18,
          backgroundImage: 'none',
          boxShadow: `0 1px 2px ${alpha(
            colours.ink,
            0.025
          )}, 0 8px 24px ${alpha(colours.ink, 0.035)}`,
        },
      },
    },

    MuiCardContent: {
      styleOverrides: {
        root: {
          padding: 24,

          '&:last-child': {
            paddingBottom: 24,
          },
        },
      },
    },

    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          minHeight: 46,
          borderRadius: 12,
          backgroundColor: colours.surface,
          transition:
            'box-shadow 160ms ease, background-color 160ms ease',

          '& .MuiOutlinedInput-notchedOutline': {
            borderColor: alpha(colours.ink, 0.14),
          },

          '&:hover .MuiOutlinedInput-notchedOutline': {
            borderColor: alpha(colours.ink, 0.28),
          },

          '&.Mui-focused': {
            boxShadow: `0 0 0 4px ${alpha(
              colours.primary,
              0.1
            )}`,
          },

          '&.Mui-focused .MuiOutlinedInput-notchedOutline': {
            borderWidth: 1,
            borderColor: colours.primary,
          },

          '&.Mui-error': {
            boxShadow: `0 0 0 4px ${alpha(
              colours.error,
              0.08
            )}`,
          },
        },

        input: {
          paddingBlock: 12,
        },
      },
    },

    MuiInputLabel: {
      styleOverrides: {
        root: {
          fontWeight: 540,
        },
      },
    },

    MuiFormHelperText: {
      styleOverrides: {
        root: {
          marginLeft: 2,
          marginTop: 6,
          lineHeight: 1.45,
        },
      },
    },

    MuiChip: {
      styleOverrides: {
        root: {
          height: 28,
          borderRadius: 9,
          fontWeight: 620,
        },

        label: {
          paddingInline: 10,
        },

        outlined: {
          borderColor: alpha(colours.ink, 0.12),
        },
      },
    },

    MuiAlert: {
      styleOverrides: {
        root: {
          borderRadius: 13,
          alignItems: 'center',
          border: `1px solid ${alpha(colours.ink, 0.07)}`,
        },

        message: {
          width: '100%',
        },
      },
    },

    MuiDialog: {
      styleOverrides: {
        paper: {
          borderRadius: 20,
          border: `1px solid ${alpha(colours.ink, 0.08)}`,
          boxShadow: `0 30px 80px ${alpha(
            colours.ink,
            0.18
          )}`,
        },
      },
    },

    MuiMenu: {
      styleOverrides: {
        paper: {
          marginTop: 8,
          borderRadius: 14,
          border: `1px solid ${alpha(colours.ink, 0.08)}`,
          boxShadow: `0 18px 48px ${alpha(
            colours.ink,
            0.14
          )}`,
        },
      },
    },

    MuiMenuItem: {
      styleOverrides: {
        root: {
          minHeight: 42,
          marginInline: 6,
          borderRadius: 9,
        },
      },
    },

    MuiTooltip: {
      styleOverrides: {
        tooltip: {
          padding: '8px 11px',
          borderRadius: 8,
          fontSize: '0.75rem',
          fontWeight: 560,
          backgroundColor: colours.ink,
          boxShadow: `0 8px 24px ${alpha(
            colours.ink,
            0.2
          )}`,
        },

        arrow: {
          color: colours.ink,
        },
      },
    },

    MuiAppBar: {
      defaultProps: {
        elevation: 0,
      },

      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },

    MuiSkeleton: {
      defaultProps: {
        animation: 'wave',
      },

      styleOverrides: {
        root: {
          borderRadius: 8,
          backgroundColor: alpha(colours.ink, 0.07),
        },
      },
    },

    MuiDivider: {
      styleOverrides: {
        root: {
          borderColor: alpha(colours.ink, 0.075),
        },
      },
    },
  },
});

export const labTheme = responsiveFontSizes(baseTheme, {
  breakpoints: ['sm', 'md', 'lg'],
  factor: 2.5,
});