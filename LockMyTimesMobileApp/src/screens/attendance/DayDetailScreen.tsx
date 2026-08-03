import { useQuery } from '@tanstack/react-query';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchAttendanceDay } from '../../api/endpoints/attendance';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';

const STANDARD_WORKDAY_HOURS = 8;

type Props = NativeStackScreenProps<AttendanceStackParamList, 'DayDetail'>;

function formatTime(iso: string | null) {
  if (!iso) return '—';
  return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export function DayDetailScreen({ route }: Props) {
  const theme = useTheme();
  const { date } = route.params;

  const { data, isLoading } = useQuery({
    queryKey: ['attendance', 'day', date],
    queryFn: () => fetchAttendanceDay(date),
  });

  if (isLoading) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const att = data?.attendance;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>
          {new Date(date + 'T00:00:00').toLocaleDateString(undefined, {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
          })}
        </Text>

        {data?.holiday_name && (
          <Text style={[typography.body, { color: theme.primary, marginTop: spacing.xs }]}>
            {data.holiday_name} (holiday)
          </Text>
        )}

        {!att ? (
          <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No attendance recorded for this day.
          </Text>
        ) : (
          <>
            <MotiView {...entranceStagger(0)} style={[
                styles.card,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}>
              <View style={styles.hoursRow}>
                <ProgressRing
                  percent={(att.total_hours / STANDARD_WORKDAY_HOURS) * 100}
                  size={88}
                  strokeWidth={8}
                  gradientColors={theme.gradients.accent}
                >
                  <Text style={[typography.subheading, { color: theme.text }]}>{att.total_hours}h</Text>
                </ProgressRing>
                <View style={styles.hoursInfo}>
                  <StatNumber value={`${att.overtime_hours}h`} label="Overtime" />
                  {att.is_late && (
                    <View style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}>
                      <StatusBadge value="late" label={`Late ${att.late_minutes}m`} color={theme.warning} filled />
                    </View>
                  )}
                </View>
              </View>

              <Row label="Clock in" value={formatTime(att.clock_in_at)} theme={theme} />
              <Row label="Clock out" value={formatTime(att.clock_out_at)} theme={theme} />
              <Row label="Location" value={att.location?.name ?? '—'} theme={theme} />
            </MotiView>

            {att.breaks.length > 0 && (
              <MotiView {...entranceStagger(1)} style={[
                styles.card,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}>
                <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Breaks</Text>
                {att.breaks.map((b) => (
                  <Row
                    key={b.id}
                    label={b.break_type}
                    value={`${formatTime(b.start_at)} – ${formatTime(b.end_at)}`}
                    theme={theme}
                  />
                ))}
              </MotiView>
            )}

            {data?.shift && (
              <MotiView {...entranceStagger(2)} style={[
                styles.card,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}>
                <Row label="Shift" value={`${data.shift.name} · ${data.shift.label}`} theme={theme} />
              </MotiView>
            )}
          </>
        )}
      </ScrollView>
    </Screen>
  );
}

function Row({
  label,
  value,
  theme,
  valueColor,
}: {
  label: string;
  value: string;
  theme: ReturnType<typeof useTheme>;
  valueColor?: string;
}) {
  return (
    <View style={styles.row}>
      <Text style={[typography.body, { color: theme.textMuted }]}>{label}</Text>
      <Text style={[typography.body, { color: valueColor ?? theme.text, fontWeight: '600' }]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  card: {
    borderRadius: radii.xl,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.xs + 2,
  },
  hoursRow: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.md },
  hoursInfo: { marginLeft: spacing.lg, flex: 1 },
});
