import { MotiText, MotiView } from 'moti';
import { StyleSheet } from 'react-native';
import { radii, spacing, typography } from '../../theme/tokens';
import { morph } from '../../theme/motion';

/**
 * Status-transition morph (see build plan B7) — cross-fade + scale instead
 * of an instant color swap whenever a status value changes. Keyed on the
 * value itself so Moti replays the transition on every change.
 */
export function StatusBadge({
  value,
  label,
  color,
  uppercase = true,
  filled = false,
}: {
  value: string;
  label?: string;
  color: string;
  uppercase?: boolean;
  /** Renders a colored-background pill instead of bare colored text. Default false = unchanged behavior. */
  filled?: boolean;
}) {
  if (!filled) {
    return (
      <MotiText
        key={value}
        from={{ opacity: 0, scale: 0.85 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={morph.transition}
        style={[styles.text, { color }, uppercase && styles.uppercase]}
      >
        {label ?? value}
      </MotiText>
    );
  }

  return (
    <MotiView
      key={value}
      from={{ opacity: 0, scale: 0.85 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={morph.transition}
      style={[styles.pill, { backgroundColor: color + '22' }]}
    >
      <MotiText style={[styles.text, { color }, uppercase && styles.uppercase]}>{label ?? value}</MotiText>
    </MotiView>
  );
}

const styles = StyleSheet.create({
  text: { fontSize: typography.caption.fontSize, fontWeight: '700' },
  uppercase: { textTransform: 'uppercase' },
  pill: {
    borderRadius: radii.pill,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.xs,
    alignSelf: 'flex-start',
  },
});
