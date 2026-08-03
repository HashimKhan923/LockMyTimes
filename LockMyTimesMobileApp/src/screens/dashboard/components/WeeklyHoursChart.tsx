import { Platform, StyleSheet, Text, View } from 'react-native';
import Svg, { Defs, LinearGradient, Rect, Stop } from 'react-native-svg';
import { elevatedShadow, radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';

const CHART_HEIGHT = 100;
const MAX_HOURS = 10;

export interface DayHours {
  label: string;
  hours: number;
}

/** Each day's bar cycles through the categorical palette for a colorful, at-a-glance week — mirrors the reference app's multi-color activity chart. */
export function WeeklyHoursChart({
  days,
  totalHours,
  presentDays,
}: {
  days: DayHours[];
  totalHours: number;
  presentDays: number;
}) {
  const theme = useTheme();
  const slot = 100 / Math.max(days.length, 1);
  const barWidth = slot * 0.46;

  return (
    <View
      style={[
        styles.card,
        { backgroundColor: theme.surfaceAlt },
        Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
      ]}
    >
      <Text style={[typography.subheading, { color: theme.text }]}>Completed in the last 7 days</Text>

      {totalHours === 0 ? (
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
          No hours logged yet this week.
        </Text>
      ) : (
        <View style={styles.chartRow}>
          <Svg width="100%" height={CHART_HEIGHT} viewBox={`0 0 100 ${CHART_HEIGHT}`} preserveAspectRatio="none">
            <Defs>
              {days.map((_, i) => {
                const c1 = theme.categorical[i % theme.categorical.length];
                const c2 = theme.categorical[(i + 3) % theme.categorical.length];
                return (
                  <LinearGradient id={`barGradient-${i}`} key={i} x1="0" y1="0" x2="0" y2="1">
                    <Stop offset="0" stopColor={c1} />
                    <Stop offset="1" stopColor={c2} />
                  </LinearGradient>
                );
              })}
            </Defs>
            {days.map((d, i) => {
              const barHeight = Math.max(6, (Math.min(d.hours, MAX_HOURS) / MAX_HOURS) * (CHART_HEIGHT - 4));
              const x = i * slot + (slot - barWidth) / 2;
              return (
                <Rect
                  key={i}
                  x={x}
                  y={CHART_HEIGHT - barHeight}
                  width={barWidth}
                  height={barHeight}
                  rx={barWidth / 2}
                  fill={`url(#barGradient-${i})`}
                />
              );
            })}
          </Svg>
        </View>
      )}

      <View style={styles.labelRow}>
        {days.map((d, i) => (
          <Text key={i} style={[typography.caption, { color: theme.textMuted, width: `${100 / days.length}%`, textAlign: 'center' }]}>
            {d.label}
          </Text>
        ))}
      </View>

      <View style={[styles.footerRow, { borderTopColor: theme.border }]}>
        <Text style={[typography.body, { color: theme.primary, fontWeight: '700' }]}>{totalHours.toFixed(1)}h total</Text>
        <View style={styles.dotDivider} />
        <Text style={[typography.body, { color: theme.accentOrange, fontWeight: '700' }]}>{presentDays} day(s) present</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: radii.xl, padding: spacing.lg },
  chartRow: { marginTop: spacing.md },
  labelRow: { flexDirection: 'row', marginTop: spacing.xs },
  footerRow: { marginTop: spacing.md, paddingTop: spacing.sm, borderTopWidth: StyleSheet.hairlineWidth, flexDirection: 'row', alignItems: 'center' },
  dotDivider: { width: 4, height: 4, borderRadius: 2, backgroundColor: '#00000022', marginHorizontal: spacing.sm },
});