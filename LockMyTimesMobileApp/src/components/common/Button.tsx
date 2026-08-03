import { ActivityIndicator, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import Animated from 'react-native-reanimated';
import { Icon } from './Icon';
import { radii, spacing, typography } from '../../theme/tokens';
import { usePressScale } from '../../theme/motion';
import { useTheme } from '../../theme/useTheme';

interface ButtonProps {
  title: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  /**
   * primary: solid filled brand color — the default, confident CTA (Submit,
   * Save, Apply, Add Task). This is what a user's eye should land on first.
   * secondary: neutral tinted, for a less prominent action alongside a primary.
   * danger: solid filled destructive color — equally confident as primary,
   * never washed out just because it's "risky."
   * accent: solid white with primary-colored text — for placing a strong
   * button directly on top of a colored/gradient hero card, where a filled
   * primary button would blend in and a white outline would look weak.
   */
  variant?: 'primary' | 'secondary' | 'danger' | 'accent';
  icon?: string;
  /** Shrinks to fit its content instead of stretching full width. */
  compact?: boolean;
}

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

export function Button({ title, onPress, loading, disabled, variant = 'primary', icon, compact }: ButtonProps) {
  const theme = useTheme();
  const { style: pressStyle, onPressIn, onPressOut } = usePressScale(0.97);

  const isDisabled = disabled || loading;

  const fill =
    variant === 'danger' ? theme.danger : variant === 'accent' ? '#FFFFFF' : variant === 'secondary' ? theme.surfaceAlt : theme.primary;
  const textColor =
    variant === 'accent' ? theme.primary : variant === 'secondary' ? theme.text : '#FFFFFF';
  const glowColor = variant === 'danger' ? theme.danger : variant === 'accent' ? undefined : theme.primary;

  return (
    <AnimatedPressable
      onPressIn={onPressIn}
      onPressOut={onPressOut}
      onPress={onPress}
      disabled={isDisabled}
      style={[
        styles.base,
        pressStyle,
        compact && styles.compact,
        {
          backgroundColor: fill,
          borderColor: variant === 'secondary' ? theme.border : 'transparent',
          opacity: isDisabled ? 0.5 : 1,
        },
        !isDisabled &&
          glowColor &&
          Platform.select({
            ios: { shadowColor: glowColor, shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.32, shadowRadius: 14 },
            android: { elevation: 3 },
          }),
      ]}
    >
      {loading ? (
        <ActivityIndicator color={textColor} />
      ) : (
        <View style={styles.accentContent}>
          {icon && <Icon name={icon} size={16} color={textColor} weight="bold" />}
          <Text style={[styles.label, { color: textColor }]}>{title}</Text>
        </View>
      )}
    </AnimatedPressable>
  );
}

const styles = StyleSheet.create({
  base: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    borderRadius: radii.md,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  compact: { alignSelf: 'flex-start' },
  accentContent: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  label: {
    fontSize: typography.subheading.fontSize,
    fontWeight: '700',
  },
});