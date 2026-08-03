import { LinearGradient } from 'expo-linear-gradient';
import type { ReactNode } from 'react';
import { Platform, View, type StyleProp, type ViewStyle } from 'react-native';
import { radii, spacing } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface GradientCardProps {
  /** Two (or more) color stops. Defaults to theme.gradients.accent (purple -> pink). */
  colors?: readonly [string, string, ...string[]];
  start?: { x: number; y: number };
  end?: { x: number; y: number };
  radius?: number;
  /** Glow shadow color; defaults to colors[0]. */
  glowColor?: string;
  /** Outer (shadow-casting) wrapper style — margin, width, flex. */
  style?: StyleProp<ViewStyle>;
  /** Inner (clipped) content style — defaults to { padding: spacing.lg }. */
  contentStyle?: StyleProp<ViewStyle>;
  children?: ReactNode;
}

/**
 * The one place per screen allowed to flood the accent (Nocturne's "stat
 * band as presence" exception) — a financial or approvals summary. Carries
 * a soft sheen overlay instead of a flat fill so it still reads as depth,
 * not a solid block.
 */
export function GradientCard({
  colors,
  start = { x: 0, y: 0 },
  end = { x: 1, y: 1 },
  radius = radii.lg,
  glowColor,
  style,
  contentStyle,
  children,
}: GradientCardProps) {
  const theme = useTheme();
  const fill = colors ?? theme.gradients.accent;
  const glow = glowColor ?? fill[0];

  return (
    <View
      style={[
        {
          borderRadius: radius,
          backgroundColor: fill[0],
          ...Platform.select({
            ios: {
              shadowColor: glow,
              shadowOffset: { width: 0, height: 10 },
              shadowOpacity: 0.32,
              shadowRadius: 22,
            },
            android: { elevation: 10, shadowColor: glow },
          }),
        },
        style,
      ]}
    >
      <LinearGradient
        colors={fill}
        start={start}
        end={end}
        style={[{ borderRadius: radius, overflow: 'hidden', padding: spacing.lg }, contentStyle]}
      >
        <LinearGradient
          colors={['rgba(255,255,255,0.16)', 'rgba(255,255,255,0)']}
          start={{ x: 0, y: 0 }}
          end={{ x: 0.6, y: 0.9 }}
          style={{ position: 'absolute', top: 0, left: 0, right: 0, height: '65%' }}
          pointerEvents="none"
        />
        {children}
      </LinearGradient>
    </View>
  );
}
