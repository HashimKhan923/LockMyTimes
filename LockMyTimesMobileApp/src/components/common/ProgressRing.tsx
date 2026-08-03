import type { ReactNode } from 'react';
import { useEffect } from 'react';
import { StyleSheet, View, type ViewStyle } from 'react-native';
import Animated, { Easing, useAnimatedProps, useSharedValue, withTiming } from 'react-native-reanimated';
import Svg, { Circle, Defs, LinearGradient as SvgGradient, Stop } from 'react-native-svg';
import { useTheme } from '../../theme/useTheme';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

export interface ProgressRingProps {
  /** 0-100. Values outside are clamped. */
  percent: number;
  size?: number;
  strokeWidth?: number;
  /** Solid stroke color — takes precedence over gradientColors. */
  color?: string;
  /** SVG-internal gradient stroke (react-native-svg's own <Defs>, not expo-linear-gradient). */
  gradientColors?: readonly [string, string];
  trackColor?: string;
  duration?: number;
  style?: ViewStyle;
  children?: ReactNode;
}

export function ProgressRing({
  percent,
  size = 96,
  strokeWidth = 10,
  color,
  gradientColors,
  trackColor,
  duration = 700,
  style,
  children,
}: ProgressRingProps) {
  const theme = useTheme();
  const clamped = Math.max(0, Math.min(100, percent));

  const radius = (size - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;

  const progress = useSharedValue(0);
  useEffect(() => {
    progress.value = withTiming(clamped / 100, { duration, easing: Easing.out(Easing.cubic) });
  }, [clamped, duration, progress]);

  const animatedProps = useAnimatedProps(() => ({
    strokeDashoffset: circumference * (1 - progress.value),
  }));

  const gradientId = 'progressRingGradient';
  const strokeColor = color ?? (gradientColors ? `url(#${gradientId})` : theme.primary);

  return (
    <View style={[{ width: size, height: size }, style]}>
      <Svg width={size} height={size} style={StyleSheet.absoluteFill}>
        {gradientColors && !color && (
          <Defs>
            <SvgGradient id={gradientId} x1="0" y1="0" x2="1" y2="1">
              <Stop offset="0" stopColor={gradientColors[0]} />
              <Stop offset="1" stopColor={gradientColors[1]} />
            </SvgGradient>
          </Defs>
        )}
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={trackColor ?? theme.surfaceAlt}
          strokeWidth={strokeWidth}
          fill="none"
        />
        <AnimatedCircle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={strokeColor}
          strokeWidth={strokeWidth}
          strokeLinecap="round"
          fill="none"
          strokeDasharray={`${circumference} ${circumference}`}
          animatedProps={animatedProps}
          transform={`rotate(-90 ${size / 2} ${size / 2})`}
        />
      </Svg>
      {children && <View style={[StyleSheet.absoluteFill, styles.center]}>{children}</View>}
    </View>
  );
}

const styles = StyleSheet.create({ center: { alignItems: 'center', justifyContent: 'center' } });
