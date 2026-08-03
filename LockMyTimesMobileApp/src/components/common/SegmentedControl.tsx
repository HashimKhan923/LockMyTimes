import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface SegmentedControlProps<T extends string> {
  options: readonly T[];
  value: T;
  onChange: (value: T) => void;
}

/** Rounded pill segmented control — active option gets a white raised chip, matching the reference's Balances/History switcher. */
export function SegmentedControl<T extends string>({ options, value, onChange }: SegmentedControlProps<T>) {
  const theme = useTheme();
  return (
    <View style={[styles.track, { backgroundColor: theme.surfaceAlt }]}>
      {options.map((option) => {
        const active = option === value;
        return (
          <Pressable
            key={option}
            onPress={() => onChange(option)}
            style={[
              styles.segment,
              active && { backgroundColor: theme.surface },
              active && (Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android),
            ]}
          >
            <Text style={[typography.body, { color: active ? theme.primary : theme.textMuted, fontWeight: active ? '700' : '500' }]}>
              {option}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  track: { flexDirection: 'row', borderRadius: radii.pill, padding: 4 },
  segment: { flex: 1, alignItems: 'center', paddingVertical: spacing.sm, borderRadius: radii.pill },
});
