import type { MotiTransitionProp } from 'moti';
import { Easing, useAnimatedStyle, useSharedValue, withSpring, withTiming } from 'react-native-reanimated';

/**
 * Shared motion language — reused everywhere instead of one-off animations
 * per screen:
 *  1. entranceStagger — list/card fade+translateY-in, first-mount only
 *  2. sheetSpring      — bottom-sheet modals (clock-in, filters, breaks)
 *  3. morph            — status-changing badges/chips
 *  4. heroSpring       — floaty, weightier entrance for hero cards & glows
 *  5. usePressScale    — shared press-down micro-interaction for any Pressable
 *  6. countUpTiming    — animated number roll-ups (StatNumber, hero hours)
 *  7. successBurst     — the clock-in/out celebratory moment
 */

export const springTransition: MotiTransitionProp = {
  type: 'spring',
  damping: 18,
  stiffness: 220,
  mass: 0.9,
};

export const sheetSpring: MotiTransitionProp = {
  type: 'spring',
  damping: 22,
  stiffness: 260,
};

export const heroSpring: MotiTransitionProp = {
  type: 'spring',
  damping: 16,
  stiffness: 140,
  mass: 1,
};

export const successBurst: MotiTransitionProp = {
  type: 'spring',
  damping: 11,
  stiffness: 170,
};

export const entranceStagger = (index: number) => ({
  from: { opacity: 0, translateY: 14 },
  animate: { opacity: 1, translateY: 0 },
  transition: { ...springTransition, delay: index * 45 },
});

export const staggerFast = (index: number) => ({
  from: { opacity: 0, translateY: 8, scale: 0.97 },
  animate: { opacity: 1, translateY: 0, scale: 1 },
  transition: { ...sheetSpring, delay: index * 25 },
});

export const fadeIn = {
  from: { opacity: 0 },
  animate: { opacity: 1 },
  transition: { type: 'timing' as const, duration: 200 },
};

export const morph = {
  transition: { type: 'timing' as const, duration: 180 },
};

export const countUpTiming = { duration: 900, easing: Easing.out(Easing.cubic) };

/**
 * Shared press-down interaction: scale to `target` on press-in, spring back
 * on release. Spread the returned onPressIn/onPressOut/style onto any
 * Animated.createAnimatedComponent(Pressable).
 */
export function usePressScale(target = 0.96) {
  const scale = useSharedValue(1);
  const style = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  return {
    style,
    onPressIn: () => {
      scale.value = withSpring(target, { damping: 16, stiffness: 320 });
    },
    onPressOut: () => {
      scale.value = withSpring(1, { damping: 14, stiffness: 260 });
    },
  };
}

/** Continuous slow drift used behind hero/glow surfaces — subtle, never distracting. */
export function useAmbientPulse(min = 0.92, max = 1) {
  const value = useSharedValue(min);
  return { value, min, max };
}

export const shimmerTiming = { duration: 900, easing: Easing.inOut(Easing.ease) };
