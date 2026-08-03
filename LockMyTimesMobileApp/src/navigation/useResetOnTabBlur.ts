import { useEffect } from 'react';
import type { ParamListBase } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

/**
 * Call from a tab's root screen so the tab's stack pops back to that root
 * screen whenever the tab is revisited — two distinct triggers:
 *
 * 1. 'blur' — the tab loses focus because a different tab was picked.
 * 2. 'tabPress' — the tab's own button was pressed again while already
 *    active. The default tab bar resets to the root screen for free on a
 *    repeat tap; this app's custom AnimatedTabBar re-emits 'tabPress' on
 *    every press (see AnimatedTabBar.tsx) but doesn't reset anything itself,
 *    so without this listener re-tapping an already-active tab does nothing.
 *
 * Without both, React Navigation keeps whatever screen you drilled into and
 * shows it again — whether you left and came back, or just tapped the tab
 * you were already on.
 */
export function useResetOnTabBlur(navigation: NativeStackNavigationProp<ParamListBase>) {
  useEffect(() => {
    const parent = navigation.getParent();
    if (!parent) return;

    // Only pop if there's actually something to pop — calling popToTop()
    // while already at the root dispatches POP_TO_TOP with nothing to do,
    // which bubbles up unhandled and logs a (harmless but noisy) error.
    function resetIfNeeded() {
      if (navigation.getState()?.index > 0) {
        navigation.popToTop();
      }
    }

    const unsubscribeBlur = parent.addListener('blur', resetIfNeeded);
    // 'tabPress' isn't part of the core NavigationProp event map (it's
    // specific to bottom-tabs), and getParent()'s static type can't know
    // its immediate parent is a tab navigator — cast is the documented
    // escape hatch for listening to a navigator-specific event this way.
    const unsubscribeTabPress = (parent as unknown as { addListener: (event: 'tabPress', cb: () => void) => () => void }).addListener(
      'tabPress',
      resetIfNeeded
    );

    return () => {
      unsubscribeBlur();
      unsubscribeTabPress();
    };
  }, [navigation]);
}
