import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { endBreak, fetchAttendanceIndex, fetchAttendanceStatus, startBreak } from '../../api/endpoints/attendance';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { GradientCard } from '../../components/common/GradientCard';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';
import { useResetOnTabBlur } from '../../navigation/useResetOnTabBlur';
import { useLiveAttendanceTimer } from '../../hooks/useLiveAttendanceTimer';
import { formatHMS } from '../../utils/formatDuration';
import type { AttendanceDay, ClockStatus } from '../../api/types';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'AttendanceHome'>;

const CTA: Record<ClockStatus, { title: string; subtitle: string; icon: string }> = {
  not_clocked_in: { title: 'CHECK IN', subtitle: 'Tap to mark your arrival', icon: 'checkbox-outline' },
  clocked_in: { title: 'CHECK OUT', subtitle: 'Tap to mark your departure', icon: 'checkbox-outline' },
  on_break: { title: 'END BREAK', subtitle: 'Tap to resume your day', icon: 'time' },
  clocked_out: { title: 'DONE FOR TODAY', subtitle: 'See you tomorrow ✨', icon: 'checkmark-done-outline' },
};

function formatClock(d: Date) {
  return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', second: '2-digit' });
}

function formatTime(iso: string | null) {
  if (!iso) return '--:--';
  return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export function AttendanceHomeScreen({ navigation }: Props) {
  useResetOnTabBlur(navigation);
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [breakError, setBreakError] = useState<string | null>(null);
  const [now, setNow] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const indexQuery = useQuery({
    queryKey: ['attendance', 'index'],
    queryFn: () => fetchAttendanceIndex(),
  });

  const statusQuery = useQuery({
    queryKey: ['attendance', 'status'],
    queryFn: fetchAttendanceStatus,
    refetchInterval: 30_000,
  });

  const status = statusQuery.data?.status ?? indexQuery.data?.today.clock_status ?? 'not_clocked_in';
  const cta = CTA[status];

  const timer = useLiveAttendanceTimer({
    status,
    workedMinutes: statusQuery.data?.worked_minutes ?? 0,
    breakStartedAt: statusQuery.data?.break_started ?? null,
    shiftEndIso: indexQuery.data?.today.shift?.end ?? null,
  });

  const breakMutation = useMutation({
    mutationFn: () => (status === 'on_break' ? endBreak() : startBreak('tea')),
    onSuccess: () => {
      setBreakError(null);
      queryClient.invalidateQueries({ queryKey: ['attendance'] });
    },
    onError: (err) => setBreakError(extractErrorMessage(err)),
  });

  function handleCtaPress() {
    if (status === 'not_clocked_in') navigation.navigate('ClockIn', { mode: 'in' });
    else if (status === 'clocked_in') navigation.navigate('ClockIn', { mode: 'out' });
    else if (status === 'on_break') breakMutation.mutate();
  }

  function renderDay({ item, index }: { item: AttendanceDay; index: number }) {
    const dotColor =
      item.status_key === 'late'
        ? theme.warning
        : item.status_key === 'present'
          ? theme.success
          : item.status_key === 'absent'
            ? theme.danger
            : item.status_key === 'holiday' || item.status_key === 'leave'
              ? theme.primary
              : theme.border;

    return (
      <MotiView {...entranceStagger(Math.min(index, 10))}>
        <Pressable
          onPress={() => navigation.navigate('DayDetail', { date: item.date })}
          style={[
            styles.dayRow,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={[styles.dot, { backgroundColor: dotColor }]} />
          <Text style={[typography.body, { color: theme.text, flex: 1 }]}>
            {new Date(item.date + 'T00:00:00').toLocaleDateString(undefined, {
              weekday: 'short',
              month: 'short',
              day: 'numeric',
            })}
          </Text>
          {item.attendance?.total_hours ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {item.attendance.total_hours}h
            </Text>
          ) : item.holiday_name ? (
            <Text style={[typography.caption, { color: theme.primary }]}>{item.holiday_name}</Text>
          ) : null}
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <Text style={styles.clock}>{formatClock(now)}</Text>
        <Text style={styles.clockDate}>
          {now.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })}
        </Text>
      </HeroHeader>

      <View style={styles.padded}>
        <View style={[styles.miniStatus, { backgroundColor: theme.surface }, Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android]}>
          <View style={styles.miniStatusCol}>
            <View style={[styles.miniIconBadge, { backgroundColor: theme.success + '1A' }]}>
              <Icon name="checkbox-outline" size={16} color={theme.success} />
            </View>
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: 6 }]}>Check In</Text>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {formatTime(statusQuery.data?.clock_in_at ?? null)}
            </Text>
          </View>
          <Icon name="chevron-forward" size={16} color={theme.border} />
          <View style={styles.miniStatusCol}>
            <View style={[styles.miniIconBadge, { backgroundColor: theme.danger + '1A' }]}>
              <Icon name="log-out-outline" size={16} color={theme.danger} />
            </View>
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: 6 }]}>Check Out</Text>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {formatTime(statusQuery.data?.clock_out_at ?? null)}
            </Text>
          </View>
        </View>

        {(status === 'clocked_in' || status === 'on_break') && (
          <MotiView
            from={{ opacity: 0, translateY: 8 }}
            animate={{ opacity: 1, translateY: 0 }}
            style={[styles.timerCard, { backgroundColor: theme.surface }, Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android]}
          >
            <View style={styles.timerCol}>
              <Text style={[typography.caption, { color: theme.textMuted }]}>Worked</Text>
              <Text style={[styles.timerValue, { color: theme.text }]}>{formatHMS(timer.workedSeconds)}</Text>
            </View>
            <View style={[styles.timerDivider, { backgroundColor: theme.border }]} />
            {status === 'on_break' ? (
              <View style={styles.timerCol}>
                <View style={styles.liveDotRow}>
                  <MotiView
                    from={{ opacity: 0.4 }}
                    animate={{ opacity: 1 }}
                    transition={{ type: 'timing', duration: 700, loop: true, repeatReverse: true }}
                    style={[styles.liveDot, { backgroundColor: theme.warning }]}
                  />
                  <Text style={[typography.caption, { color: theme.warning, fontWeight: '700' }]}>On break</Text>
                </View>
                <Text style={[styles.timerValue, { color: theme.warning }]}>{formatHMS(timer.breakSeconds)}</Text>
              </View>
            ) : (
              <View style={styles.timerCol}>
                <Text style={[typography.caption, { color: timer.isOvertime ? theme.danger : theme.textMuted }]}>
                  {timer.isOvertime ? 'Overtime' : 'Remaining'}
                </Text>
                <Text style={[styles.timerValue, { color: timer.isOvertime ? theme.danger : theme.primary }]}>
                  {formatHMS(Math.abs(timer.remainingSeconds ?? 0))}
                </Text>
              </View>
            )}
          </MotiView>
        )}

        <MotiView from={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} style={{ marginTop: spacing.md }}>
          <Pressable onPress={handleCtaPress} disabled={status === 'clocked_out'}>
            <GradientCard colors={status === 'clocked_out' ? [theme.textMuted, theme.textMuted] : theme.gradients.accent} style={styles.ctaCard}>
              <View style={styles.ctaIconCircle}>
                <Icon name={cta.icon} size={34} color="#FFFFFF" />
              </View>
              <Text style={styles.ctaTitle}>{cta.title}</Text>
              <Text style={styles.ctaSubtitle}>{cta.subtitle}</Text>
              {status !== 'clocked_out' && (
                <View style={styles.ctaPill}>
                  <Text style={styles.ctaPillText}>QR Code or GPS available</Text>
                </View>
              )}
            </GradientCard>
          </Pressable>
          {breakError && (
            <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{breakError}</Text>
          )}
        </MotiView>

        {status === 'clocked_in' && (
          <View style={{ marginTop: spacing.sm }}>
            <Pressable
              onPress={() => breakMutation.mutate()}
              style={[styles.linkRow, { backgroundColor: theme.surface }, Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android]}
            >
              <Icon name="time" size={18} color={theme.warning} />
              <Text style={[typography.body, { color: theme.text, flex: 1, fontWeight: '600' }]}>Take a break</Text>
              <Icon name="chevron-forward" size={16} color={theme.textMuted} />
            </Pressable>
          </View>
        )}

        <View style={{ marginTop: spacing.sm }}>
          <Pressable
            onPress={() => navigation.navigate('DayDetail', { date: new Date().toISOString().slice(0, 10) })}
            style={[styles.linkRow, { backgroundColor: theme.surface }, Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android]}
          >
            <Icon name="calendar" size={18} color={theme.primary} />
            <Text style={[typography.body, { color: theme.primary, flex: 1, fontWeight: '700' }]}>View Attendance History</Text>
            <Icon name="chevron-forward" size={16} color={theme.primary} />
          </Pressable>
        </View>
      </View>

      <FlatList
        data={indexQuery.data?.days.filter((d) => d.attendance || d.holiday_name).reverse() ?? []}
        keyExtractor={(item) => item.date}
        renderItem={renderDay}
        contentContainerStyle={styles.padded}
        refreshControl={
          <AppRefreshControl
            refreshing={indexQuery.isRefetching}
            onRefresh={() => {
              indexQuery.refetch();
              statusQuery.refetch();
            }}
          />
        }
        ListEmptyComponent={
          <View style={[styles.emptyCard, { backgroundColor: theme.surface }]}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              No attendance recorded yet this month.
            </Text>
          </View>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  clock: { fontSize: 40, fontWeight: '800', color: '#FFFFFF', letterSpacing: 1 },
  clockDate: { fontSize: 14, color: 'rgba(255,255,255,0.85)', marginTop: 4, fontWeight: '600' },
  miniStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.lg,
  },
  miniStatusCol: { alignItems: 'center' },
  miniIconBadge: { width: 32, height: 32, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
  timerCard: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  timerCol: { flex: 1, alignItems: 'center' },
  timerDivider: { width: StyleSheet.hairlineWidth, alignSelf: 'stretch', marginVertical: 2 },
  timerValue: { fontSize: 22, fontWeight: '800', fontVariant: ['tabular-nums'], marginTop: 4 },
  liveDotRow: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  liveDot: { width: 6, height: 6, borderRadius: 3 },
  ctaCard: { alignItems: 'center', paddingVertical: spacing.xl },
  ctaIconCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: 'rgba(255,255,255,0.22)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  ctaTitle: { color: '#FFFFFF', fontSize: 22, fontWeight: '800', marginTop: spacing.md, letterSpacing: 0.5 },
  ctaSubtitle: { color: 'rgba(255,255,255,0.85)', marginTop: 4, fontSize: 13 },
  ctaPill: {
    marginTop: spacing.md,
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radii.pill,
  },
  ctaPillText: { color: '#FFFFFF', fontSize: 11, fontWeight: '700' },
  linkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    padding: spacing.md,
  },
  dayRow: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.md,
    borderRadius: radii.md,
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
  emptyCard: { borderRadius: radii.md, padding: spacing.md, marginTop: spacing.lg },
  dot: { width: 8, height: 8, borderRadius: 4 },
});
