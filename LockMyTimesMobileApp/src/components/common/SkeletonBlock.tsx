import { useEffect } from 'react';
import { View, type DimensionValue } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, { Easing, useAnimatedStyle, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';
import { radii } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

const AnimatedGradient = Animated.createAnimatedComponent(LinearGradient);

/**
 * Shared shimmer placeholder — a light sweep travels across the block
 * instead of a flat opacity pulse, used for every loading state.
 */
export function SkeletonBlock({
  width = '100%',
  height = 16,
  radius = radii.sm,
  style,
}: {
  width?: DimensionValue;
  height?: number;
  radius?: number;
  style?: object;
}) {
  const theme = useTheme();
  const sweep = useSharedValue(-1);

  useEffect(() => {
    sweep.value = withRepeat(withTiming(1, { duration: 1100, easing: Easing.inOut(Easing.ease) }), -1, false);
  }, [sweep]);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ translateX: sweep.value * 220 }],
  }));

  return (
    <View style={[{ width, height, borderRadius: radius, backgroundColor: theme.surfaceAlt, overflow: 'hidden' }, style]}>
      <AnimatedGradient
        colors={[theme.surfaceAlt, theme.surfaceRaised, theme.surfaceAlt]}
        start={{ x: 0, y: 0.5 }}
        end={{ x: 1, y: 0.5 }}
        style={[{ position: 'absolute', top: 0, bottom: 0, width: '60%' }, animatedStyle]}
      />
    </View>
  );
}

export function SkeletonList({ rows = 4, rowHeight = 64 }: { rows?: number; rowHeight?: number }) {
  return (
    <>
      {Array.from({ length: rows }).map((_, i) => (
        <SkeletonBlock key={i} height={rowHeight} radius={radii.md} style={{ marginTop: 12 }} />
      ))}
    </>
  );
}

/** Generic detail-screen placeholder: title + a couple of card blocks. */
export function DetailSkeleton() {
  return (
    <View style={{ paddingHorizontal: 24, paddingTop: 16 }}>
      <SkeletonBlock width="60%" height={14} />
      <SkeletonBlock width="85%" height={28} style={{ marginTop: 10 }} />
      <SkeletonBlock height={90} radius={radii.lg} style={{ marginTop: 20 }} />
      <SkeletonBlock height={120} radius={radii.lg} style={{ marginTop: 16 }} />
    </View>
  );
}
