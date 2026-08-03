import { useState } from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import { useMutation, useQueryClient, type UseQueryResult } from '@tanstack/react-query';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { extractErrorMessage } from '../../../api/client';
import { endBreak, startBreak } from '../../../api/endpoints/attendance';
import { Button } from '../../../components/common/Button';
import { GradientCard } from '../../../components/common/GradientCard';
import { ProgressRing } from '../../../components/common/ProgressRing';
import { SkeletonBlock } from '../../../components/common/SkeletonBlock';
import { heroSpring } from '../../../theme/motion';
import { radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import { useLiveAttendanceTimer } from '../../../hooks/useLiveAttendanceTimer';
import { formatHMS } from '../../../utils/formatDuration';
import type { MainTabsParamList } from '../../../navigation/MainTabs';
import type { AttendanceIndexResponse, AttendanceStatusResponse, ClockStatus } from '../../../api/types';

const STATUS_LABEL: Record<ClockStatus, string> = {
  not_clocked_in: 'Not clocked in',
  clocked_in: 'Clocked in',
  on_break: 'On break',
  clocked_out: 'Clocked out',
};

const DAILY_GOAL_HOURS = 8;

export function ClockHeroCard({
  indexQuery,
  statusQuery,
  navigation,
}: {
  indexQuery: UseQueryResult<AttendanceIndexResponse>;
  statusQuery: UseQueryResult<AttendanceStatusResponse>;
  navigation: BottomTabNavigationProp<MainTabsParamList, 'Dashboard'>;
}) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [breakError, setBreakError] = useState<string | null>(null);

  const status = statusQuery.data?.status ?? indexQuery.data?.today.clock_status ?? 'not_clocked_in';

  const timer = useLiveAttendanceTimer({
    status,
    workedMinutes: statusQuery.data?.worked_minutes ?? 0,
    breakStartedAt: statusQuery.data?.break_started ?? null,
    shiftEndIso: indexQuery.data?.today.shift?.end ?? null,
    dailyGoalMinutes: DAILY_GOAL_HOURS * 60,
  });
  const workedHours = timer.workedSeconds / 3600;
  const percent = Math.min(100, (workedHours / DAILY_GOAL_HOURS) * 100);

  const breakMutation = useMutation({
    mutationFn: () => (status === 'on_break' ? endBreak() : startBreak('tea')),
    onSuccess: () => {
      setBreakError(null);
      queryClient.invalidateQueries({ queryKey: ['attendance'] });
    },
    onError: (err) => setBreakError(extractErrorMessage(err)),
  });

  if (indexQuery.isLoading && statusQuery.isLoading) {
    return <SkeletonBlock height={210} radius={radii.xl} style={styles.hero} />;
  }

  return (
    <MotiView
      from={{ opacity: 0, scale: 0.96, translateY: 10 }}
      animate={{ opacity: 1, scale: 1, translateY: 0 }}
      transition={heroSpring}
      style={styles.hero}
    >
      <GradientCard colors={theme.gradients.accent} glowColor={theme.primary} radius={radii.xl}>
        <View style={styles.topRow}>
          <Text style={[typography.subheading, { color: '#FFFFFF' }]}>Daily Goal</Text>
          <View style={styles.todayPill}>
            <Text style={styles.todayPillText}>Today</Text>
          </View>
        </View>

        <View style={styles.mainRow}>
          <View style={styles.actionsCol}>
            <View style={styles.goalPill}>
              <Text style={styles.goalPillText}>{workedHours.toFixed(1)}/{DAILY_GOAL_HOURS}h</Text>
            </View>
            {status === 'on_break' ? (
              <View style={styles.liveRow}>
                <MotiView
                  from={{ opacity: 0.4 }}
                  animate={{ opacity: 1 }}
                  transition={{ type: 'timing', duration: 700, loop: true, repeatReverse: true }}
                  style={styles.liveDot}
                />
                <Text style={[typography.caption, styles.subtext, { marginTop: 0 }]}>
                  On break · {formatHMS(timer.breakSeconds)}
                </Text>
              </View>
            ) : status === 'clocked_in' ? (
              <Text style={[typography.caption, styles.subtext]}>
                {timer.isOvertime ? 'Overtime' : 'Remaining'} · {formatHMS(Math.abs(timer.remainingSeconds ?? 0))}
              </Text>
            ) : (
              <Text style={[typography.caption, styles.subtext]}>
                {STATUS_LABEL[status]} · {Math.round(percent)}% of today's goal
              </Text>
            )}

            <View style={styles.actions}>
              {status === 'not_clocked_in' && (
                <Button
                  title="Clock In"
                  variant="accent"
                  icon="add"
                  compact
                  onPress={() => navigation.navigate('Attendance', { screen: 'ClockIn', params: { mode: 'in' } })}
                />
              )}
              {(status === 'clocked_in' || status === 'on_break') && (
                <View style={{ flexDirection: 'row', gap: spacing.sm }}>
                  <Pressable
                    onPress={() => breakMutation.mutate()}
                    style={styles.ghostBtn}
                  >
                    <Text style={styles.ghostBtnText}>{status === 'on_break' ? 'End break' : 'Take a break'}</Text>
                  </Pressable>
                  <Button
                    title="Clock Out"
                    variant="accent"
                    compact
                    disabled={status === 'on_break'}
                    onPress={() => navigation.navigate('Attendance', { screen: 'ClockIn', params: { mode: 'out' } })}
                  />
                </View>
              )}
              {status === 'clocked_out' && (
                <Text style={[typography.body, styles.subtext]}>See you tomorrow ✨</Text>
              )}
            </View>

            {breakError && <Text style={styles.errorText}>{breakError}</Text>}
          </View>

          <ProgressRing
            percent={percent}
            size={108}
            strokeWidth={11}
            color="#FFFFFF"
            trackColor="rgba(255,255,255,0.28)"
          >
            <Text style={[typography.heading, { color: '#FFFFFF' }]}>{Math.round(percent)}%</Text>
            <Text style={[typography.caption, { color: 'rgba(255,255,255,0.8)' }]}>of {DAILY_GOAL_HOURS}h</Text>
          </ProgressRing>
        </View>

        <Pressable onPress={() => navigation.navigate('Attendance', { screen: 'AttendanceHome' })} style={styles.viewMore}>
          <Text style={styles.viewMoreText}>View attendance →</Text>
        </Pressable>
      </GradientCard>
    </MotiView>
  );
}

const styles = StyleSheet.create({
  hero: { marginTop: spacing.lg, marginBottom: spacing.md },
  topRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  todayPill: { backgroundColor: 'rgba(255,255,255,0.22)', paddingHorizontal: spacing.sm, paddingVertical: 4, borderRadius: radii.pill },
  todayPillText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  liveRow: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: spacing.xs },
  liveDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: '#FFFFFF' },
  mainRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.md },
  actionsCol: { flex: 1, marginRight: spacing.md },
  goalPill: {
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.22)',
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  goalPillText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
  subtext: { color: 'rgba(255,255,255,0.85)', marginTop: spacing.xs },
  actions: { marginTop: spacing.md },
  ghostBtn: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    borderRadius: radii.md,
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  ghostBtnText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
  errorText: { color: '#FFE1DE', marginTop: spacing.sm, fontSize: 12 },
  viewMore: { marginTop: spacing.lg },
  viewMoreText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
});