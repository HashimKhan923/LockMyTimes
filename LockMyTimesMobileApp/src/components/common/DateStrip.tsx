import { LinearGradient } from 'expo-linear-gradient';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { radii, spacing } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface DateStripDay {
  /** ISO yyyy-mm-dd */
  date: string;
  weekdayLabel: string;
  dayNumber: string;
}

/** A week of tappable day cells; the selected day rides the primary gradient. */
export function DateStrip({
  days,
  selected,
  onSelect,
}: {
  days: DateStripDay[];
  selected: string | null;
  onSelect: (date: string) => void;
}) {
  const theme = useTheme();

  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
      {days.map((d) => {
        const isSelected = d.date === selected;
        return (
          <Pressable key={d.date} onPress={() => onSelect(isSelected ? null! : d.date)} style={styles.cellWrap}>
            {isSelected ? (
              <LinearGradient
                colors={theme.gradients.accent}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={[
                  styles.cell,
                  Platform.select({
                    ios: { shadowColor: theme.primary, shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.35, shadowRadius: 10 },
                    android: { elevation: 4 },
                  }),
                ]}
              >
                <Text style={[styles.weekday, { color: '#FFFFFF', opacity: 0.85 }]}>{d.weekdayLabel}</Text>
                <Text style={[styles.dayNum, { color: '#FFFFFF' }]}>{d.dayNumber}</Text>
              </LinearGradient>
            ) : (
              <View style={[styles.cell, { backgroundColor: theme.surface, borderColor: theme.border, borderWidth: 1 }]}>
                <Text style={[styles.weekday, { color: theme.textMuted }]}>{d.weekdayLabel}</Text>
                <Text style={[styles.dayNum, { color: theme.text }]}>{d.dayNumber}</Text>
              </View>
            )}
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  row: { gap: spacing.sm, paddingVertical: spacing.xs },
  cellWrap: {},
  cell: { width: 52, height: 60, borderRadius: radii.lg, alignItems: 'center', justifyContent: 'center', gap: 4 },
  weekday: { fontSize: 11, fontWeight: '600', textTransform: 'uppercase' },
  dayNum: { fontSize: 17, fontWeight: '700' },
});