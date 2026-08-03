/**
 * Brand accent is #6C7DF7 — the same brand-500 used across the web app
 * (see tailwind.config.js / app.css --brand-500). Dark is the primary mode;
 * light is a companion derived from the same hue family so both read as the
 * same product, and both match the web app's brand identity.
 */
export const colors = {
  light: {
    background: '#ECF0FC',
    surface: '#FFFFFF',
    surfaceAlt: '#E1E6FA',
    surfaceRaised: '#FFFFFF',
    border: '#CDD5F5',
    borderStrong: '#B3BEF0',
    text: '#1A1D33',
    textMuted: '#6B6F8F',
    primary: '#6C7DF7',
    primaryMuted: '#EEF0FE',
    accentOrange: '#FF9A44',
    accentPink: '#FF6FA0',
    accentTeal: '#22C7B5',
    accentBlue: '#3D8BFF',
    success: '#1FAE7E',
    warning: '#FF9A44',
    danger: '#FF5A6E',
    onPrimary: '#FFFFFF',
  },
  dark: {
    background: '#121420',
    surface: '#1B1E30',
    surfaceAlt: '#232741',
    surfaceRaised: '#262A47',
    border: '#2E3355',
    borderStrong: '#3C4470',
    text: '#EEF0FB',
    textMuted: '#9DA3C8',
    primary: '#9AA8FA',
    primaryMuted: '#2A2F58',
    accentOrange: '#FFB067',
    accentPink: '#FF8FBB',
    accentTeal: '#4FDDCB',
    accentBlue: '#6AA5FF',
    success: '#4FE3B0',
    warning: '#FFB067',
    danger: '#FF8B93',
    onPrimary: '#121420',
  },
} as const;

export type ThemeColors = typeof colors.light;

/** [from, to] gradient stops per mode. Bold, colorful pairings — the app's signature look, used freely on hero cards, rings, chips, and chart fills, not just as a rare accent. */
export const gradients = {
  light: {
    primary: ['#9AA8FA', '#6C7DF7'] as const,
    accent: ['#6C7DF7', '#4A5BE8'] as const,
    sunset: ['#FFC371', '#FF9A44'] as const,
    berry: ['#FF8FBB', '#FF5A8A'] as const,
    ocean: ['#5FC6FF', '#3D8BFF'] as const,
    mint: ['#5FE8CE', '#1FAE7E'] as const,
    success: ['#5FE8CE', '#1FAE7E'] as const,
    warning: ['#FFC371', '#FF9A44'] as const,
    danger: ['#FF9CA8', '#FF5A6E'] as const,
  },
  dark: {
    primary: ['#BCC5FB', '#9AA8FA'] as const,
    accent: ['#9AA8FA', '#7989F8'] as const,
    sunset: ['#FFD199', '#FFB067'] as const,
    berry: ['#FFB3D2', '#FF8FBB'] as const,
    ocean: ['#8FC9FF', '#6AA5FF'] as const,
    mint: ['#7FEFDC', '#4FE3B0'] as const,
    success: ['#7FEFDC', '#4FE3B0'] as const,
    warning: ['#FFD199', '#FFB067'] as const,
    danger: ['#FFB4BA', '#FF8B93'] as const,
  },
} as const;

export type GradientTokens = typeof gradients.light;
export type GradientName = keyof GradientTokens;

/**
 * Fallback categorical palette for category/type values with no server-provided
 * `color` (e.g. an expense category or leave type missing `color`). Fixed order —
 * pick a color via `categoricalColor`, never re-sort/re-cycle, so a given
 * category keeps the same slot for its lifetime.
 */
export const categorical = {
  light: ['#7C5CFC', '#FF9A44', '#FF6FA0', '#3D8BFF', '#22C7B5', '#FFC94D', '#B15CFC', '#4CD07A'] as const,
  dark: ['#A78BFA', '#FFB067', '#FF8FBB', '#6AA5FF', '#4FDDCB', '#FFDB7A', '#CB9BFF', '#7BE39C'] as const,
} as const;

export function categoricalColor(theme: 'light' | 'dark', seed: string | number): string {
  const arr = categorical[theme];
  const key = String(seed);
  let hash = 0;
  for (let i = 0; i < key.length; i++) hash = (hash * 31 + key.charCodeAt(i)) | 0;
  return arr[Math.abs(hash) % arr.length];
}

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
} as const;

export const radii = {
  sm: 8,
  md: 12,
  lg: 18,
  xl: 24,
  pill: 999,
} as const;

/**
 * Soft elevation for cards sitting on `theme.background` (not gradient
 * cards, which carry their own colored glow via GradientCard). Cross-platform
 * via Platform.select at each call site — kept here as the one shared recipe.
 * Nocturne rule: elevation is an edge plus ambient darkness, never a heavy
 * shadow stack — keep opacity low and let `border` do most of the work.
 */
export const elevatedShadow = {
  ios: { shadowColor: '#08071A', shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.22, shadowRadius: 18 },
  android: { elevation: 3 },
} as const;

/** A short, solid accent mark — never faded, used for the current-selection rail, active tab pill, etc. */
export const accentMark = { width: 3, borderRadius: 2 } as const;

export const typography = {
  title: { fontSize: 28, fontWeight: '600' as const },
  heading: { fontSize: 20, fontWeight: '600' as const },
  subheading: { fontSize: 16, fontWeight: '600' as const },
  body: { fontSize: 15, fontWeight: '400' as const },
  caption: { fontSize: 13, fontWeight: '400' as const },
};