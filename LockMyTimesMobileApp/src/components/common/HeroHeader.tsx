import type { ReactNode } from 'react';
import { Platform, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { spacing } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface HeroHeaderProps {
  colors?: readonly [string, string, ...string[]];
  children?: ReactNode;
  style?: StyleProp<ViewStyle>;
}

/**
 * Full-bleed, rounded-bottom color block for the top of a screen — the
 * signature shape of the reference design (profile hero, greeting hero,
 * clock/check-in hero, section headers with an action). Bleeds into the
 * status bar; content below should sit in a normal padded View.
 */
export function HeroHeader({ colors, children, style }: HeroHeaderProps) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const fill = colors ?? theme.gradients.accent;

  return (
    <LinearGradient
      colors={fill}
      start={{ x: 0.1, y: 0 }}
      end={{ x: 0.9, y: 1 }}
      style={[
        styles.hero,
        { paddingTop: insets.top + spacing.md },
        Platform.select({
          ios: { shadowColor: fill[fill.length - 1], shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.25, shadowRadius: 20 },
          android: { elevation: 6 },
        }),
        style,
      ]}
    >
      <View style={styles.content}>{children}</View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  hero: {
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
    paddingBottom: spacing.xl,
  },
  content: { paddingHorizontal: 24 },
});
