import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import { useMutation, useQueryClient, type UseQueryResult } from '@tanstack/react-query';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { extractErrorMessage } from '../../../api/client';
import { endBreak, startBreak } from '../../../api/endpoints/attendance';
import { GradientCard } from '../../../components/common/GradientCard';
import { Icon } from '../../../components/common/Icon';
import { ProgressRing } from '../../../components/common/ProgressRing';
import { SkeletonBlock } from '../../../components/common/SkeletonBlock';
import { heroSpring } from '../../../theme/motion';
import { radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import { useLiveAttendanceTimer } from '../../../hooks/useLiveAttendanceTimer';
import { useToastStore } from '../../../stores/toastStore';
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
  const showToast = useToastStore((s) => s.show);

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
      queryClient.invalidateQueries({ queryKey: ['attendance'] });
      showToast(status === 'on_break' ? 'Break ended' : 'Break started', 'success');
    },
    onError: (err) => showToast(extractErrorMessage(err), 'error'),
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
          <View style={styles.statsCol}>
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
          </View>

          <ProgressRing
            percent={percent}
            size={92}
            strokeWidth={9}
            color="#FFFFFF"
            trackColor="rgba(255,255,255,0.28)"
          >
            <Text style={[typography.heading, { color: '#FFFFFF' }]}>{Math.round(percent)}%</Text>
            <Text style={[typography.caption, { color: 'rgba(255,255,255,0.8)' }]}>of {DAILY_GOAL_HOURS}h</Text>
          </ProgressRing>
        </View>

        {/* Full card width — was previously squeezed into a column next to the
            ring, which caused the buttons to overflow and visually overlap it. */}
        <View style={styles.actions}>
          {status === 'not_clocked_in' && (
            <Pressable
              onPress={() => navigation.navigate('Attendance', { screen: 'ClockIn', params: { mode: 'in' } })}
              style={styles.soloBtn}
            >
              <Icon name="add" size={16} color={theme.primary} />
              <Text style={[styles.soloBtnText, { color: theme.primary }]}>Clock In</Text>
            </Pressable>
          )}
          {(status === 'clocked_in' || status === 'on_break') && (
            <View style={styles.actionsRow}>
              <Pressable onPress={() => breakMutation.mutate()} style={styles.ghostBtn}>
                <Text style={styles.ghostBtnText}>{status === 'on_break' ? 'End break' : 'Take a break'}</Text>
              </Pressable>
              <Pressable
                onPress={() => navigation.navigate('Attendance', { screen: 'ClockIn', params: { mode: 'out' } })}
                disabled={status === 'on_break'}
                style={[styles.solidBtn, status === 'on_break' && styles.btnDisabled]}
              >
                <Text style={[styles.solidBtnText, { color: theme.primary }]}>Clock Out</Text>
              </Pressable>
            </View>
          )}
          {status === 'clocked_out' && (
            <Text style={[typography.body, styles.subtext]}>See you tomorrow ✨</Text>
          )}
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
  statsCol: { flex: 1, marginRight: spacing.md },
  goalPill: {
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.22)',
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  goalPillText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
  subtext: { color: 'rgba(255,255,255,0.85)', marginTop: spacing.xs },
  actions: { marginTop: spacing.lg },
  actionsRow: { flexDirection: 'row', gap: spacing.sm },
  // Take a break / Clock Out / solo Clock In all share the same padding,
  // radius, and font size so they read as one consistent button language
  // regardless of fill (solid white vs. outlined) or row position.
  ghostBtn: {
    flex: 1,
    paddingVertical: spacing.sm + 4,
    paddingHorizontal: spacing.sm,
    borderRadius: radii.md,
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  ghostBtnText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
  solidBtn: {
    flex: 1,
    paddingVertical: spacing.sm + 4,
    paddingHorizontal: spacing.sm,
    borderRadius: radii.md,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  solidBtnText: { fontWeight: '700', fontSize: 13 },
  soloBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.sm + 4,
    borderRadius: radii.md,
    backgroundColor: '#FFFFFF',
  },
  soloBtnText: { fontWeight: '700', fontSize: 14 },
  btnDisabled: { opacity: 0.5 },
  viewMore: { marginTop: spacing.lg },
  viewMoreText: { color: '#FFFFFF', fontWeight: '700', fontSize: 13 },
});