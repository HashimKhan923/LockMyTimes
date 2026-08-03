import { useQuery } from '@tanstack/react-query';
import { FlatList, Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { MotiView } from 'moti';
import { Icon } from '../../components/common/Icon';
import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import { fetchAttendanceIndex, fetchAttendanceStatus } from '../../api/endpoints/attendance';
import { fetchLeaves } from '../../api/endpoints/leaves';
import { fetchTasks } from '../../api/endpoints/tasks';
import { fetchAnnouncements } from '../../api/endpoints/announcements';
import { fetchPayslips } from '../../api/endpoints/payslips';
import { fetchProfile } from '../../api/endpoints/profile';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { QuickActionTile, styles as tileStyles } from '../../components/common/QuickActionTile';
import { Screen } from '../../components/common/Screen';
import { useAuthStore } from '../../stores/authStore';
import { entranceStagger } from '../../theme/motion';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MainTabsParamList } from '../../navigation/MainTabs';
import { ClockHeroCard } from './components/ClockHeroCard';
import { QuickStatsRow } from './components/QuickStatsRow';
import { AnnouncementsFeedCard } from './components/AnnouncementsFeedCard';
import { PendingItemsCard } from './components/PendingItemsCard';
import { WeeklyHoursChart, type DayHours } from './components/WeeklyHoursChart';

type Props = BottomTabScreenProps<MainTabsParamList, 'Dashboard'>;

const SECTIONS = ['actions', 'chart', 'stats', 'announcements', 'pending'] as const;

export function DashboardScreen({ navigation }: Props) {
  const theme = useTheme();
  const user = useAuthStore((s) => s.user);

  const indexQuery = useQuery({ queryKey: ['attendance', 'index'], queryFn: () => fetchAttendanceIndex() });
  const statusQuery = useQuery({
    queryKey: ['attendance', 'status'],
    queryFn: fetchAttendanceStatus,
    refetchInterval: 30_000,
  });
  const leavesQuery = useQuery({ queryKey: ['leaves', 'index'], queryFn: () => fetchLeaves() });
  const tasksQuery = useQuery({ queryKey: ['tasks', 'open'], queryFn: () => fetchTasks({ filter: 'open' }) });
  const announcementsQuery = useQuery({ queryKey: ['announcements', 'index'], queryFn: () => fetchAnnouncements() });
  const payslipsQuery = useQuery({ queryKey: ['payslips', 'index'], queryFn: () => fetchPayslips() });
  // Same query the Profile screen uses — the cached auth session's
  // avatar_url can go stale (it's only refreshed on login), so prefer this
  // fresher source and only fall back to the session copy before it loads.
  const profileQuery = useQuery({ queryKey: ['profile'], queryFn: fetchProfile });
  const avatarUrl = profileQuery.data?.employee.avatar_url ?? user?.avatar_url;

  const refetchAll = () => {
    indexQuery.refetch();
    statusQuery.refetch();
    leavesQuery.refetch();
    tasksQuery.refetch();
    announcementsQuery.refetch();
    payslipsQuery.refetch();
    profileQuery.refetch();
  };

  const weekDays: DayHours[] = (indexQuery.data?.days ?? [])
    .slice(-7)
    .map((d) => ({
      label: new Date(d.date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'narrow' }),
      hours: d.attendance?.total_hours ?? 0,
    }));
  const weekTotalHours = weekDays.reduce((sum, d) => sum + d.hours, 0);
  const weekPresentDays = weekDays.filter((d) => d.hours > 0).length;

  const initials = (user?.name ?? '')
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  const unreadCount = announcementsQuery.data?.counters.unread ?? 0;
  const today = new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' });

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <MotiView {...entranceStagger(0)} style={styles.headerRow}>
          <Pressable
            onPress={() => navigation.navigate('More', { screen: 'Profile' })}
            style={styles.headerLeft}
          >
            <LinearGradient colors={theme.gradients.accent} style={styles.avatarRing}>
              {avatarUrl ? (
                <Image source={{ uri: avatarUrl }} style={styles.avatar} />
              ) : (
                <View style={[styles.avatar, styles.avatarFallback, { backgroundColor: theme.surface }]}>
                  <Text style={{ color: theme.primary, fontWeight: '700' }}>{initials}</Text>
                </View>
              )}
            </LinearGradient>
            <View style={{ marginLeft: spacing.sm }}>
              <Text style={[typography.caption, { color: theme.textMuted }]}>Hello 👋</Text>
              <Text style={[typography.title, { color: theme.text }]}>{user?.name?.split(' ')[0]}</Text>
            </View>
          </Pressable>

          <View style={styles.headerActions}>
            <Pressable
              onPress={() => navigation.navigate('More', { screen: 'Announcements', params: { screen: 'AnnouncementList' } })}
              style={[styles.iconButton, { backgroundColor: theme.surfaceAlt, borderColor: theme.border }]}
            >
              <Icon name="chatbubble-outline" size={19} color={theme.text} />
              {unreadCount > 0 && <View style={[styles.dot, { backgroundColor: theme.danger, borderColor: theme.background }]} />}
            </Pressable>
            <Pressable
              onPress={() => navigation.navigate('More', { screen: 'Notifications' })}
              style={[styles.iconButton, { backgroundColor: theme.surfaceAlt, borderColor: theme.border }]}
            >
              <Icon name="notifications-outline" size={19} color={theme.text} />
            </Pressable>
          </View>
        </MotiView>

        <View style={styles.overviewRow}>
          <Text style={[typography.subheading, { color: theme.text }]}>Overview</Text>
          <LinearGradient colors={theme.gradients.accent} style={styles.overviewPill}>
            <Text style={styles.overviewPillText}>{today}</Text>
          </LinearGradient>
        </View>

        <ClockHeroCard indexQuery={indexQuery} statusQuery={statusQuery} navigation={navigation} />
      </View>

      <FlatList
        data={SECTIONS}
        keyExtractor={(section) => section}
        renderItem={({ item, index }) => (
          <MotiView {...entranceStagger(index + 1)} style={styles.sectionWrap}>
            {item === 'actions' && (
              <>
                <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Quick Actions</Text>
                <View style={tileStyles.grid}>
                  <QuickActionTile
                    icon="checkbox-outline"
                    label="Check In/Out"
                    color={theme.primary}
                    onPress={() => navigation.navigate('Attendance', { screen: 'ClockIn', params: { mode: 'in' } })}
                  />
                  <QuickActionTile
                    icon="calendar"
                    label="History"
                    color={theme.accentBlue}
                    onPress={() => navigation.navigate('Attendance', { screen: 'AttendanceHome' })}
                  />
                  <QuickActionTile
                    icon="document-text-outline"
                    label="Apply Leave"
                    color={theme.categorical[2]}
                    onPress={() => navigation.navigate('Leaves', { screen: 'LeaveApply' })}
                  />
                  <QuickActionTile
                    icon="receipt-outline"
                    label="Payslips"
                    color={theme.categorical[4]}
                    onPress={() => navigation.navigate('More', { screen: 'PayslipList' })}
                  />
                </View>
              </>
            )}
            {item === 'chart' && (
              <WeeklyHoursChart days={weekDays} totalHours={weekTotalHours} presentDays={weekPresentDays} />
            )}
            {item === 'stats' && (
              <QuickStatsRow
                indexQuery={indexQuery}
                leavesQuery={leavesQuery}
                tasksQuery={tasksQuery}
                announcementsQuery={announcementsQuery}
                navigation={navigation}
              />
            )}
            {item === 'announcements' && (
              <AnnouncementsFeedCard announcementsQuery={announcementsQuery} navigation={navigation} />
            )}
            {item === 'pending' && (
              <PendingItemsCard
                leavesQuery={leavesQuery}
                tasksQuery={tasksQuery}
                payslipsQuery={payslipsQuery}
                navigation={navigation}
              />
            )}
          </MotiView>
        )}
        contentContainerStyle={[styles.padded, { paddingBottom: spacing.xxl }]}
        refreshControl={<AppRefreshControl refreshing={indexQuery.isRefetching} onRefresh={refetchAll} />}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  sectionWrap: { paddingHorizontal: 24, marginTop: spacing.md },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.md },
  headerLeft: { flexDirection: 'row', alignItems: 'center' },
  headerActions: { flexDirection: 'row', gap: spacing.sm },
  avatarRing: { width: 48, height: 48, borderRadius: radii.pill, padding: 2, alignItems: 'center', justifyContent: 'center' },
  avatar: { width: 44, height: 44, borderRadius: radii.pill },
  avatarFallback: { alignItems: 'center', justifyContent: 'center' },
  iconButton: { width: 40, height: 40, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center', borderWidth: 1 },
  dot: { position: 'absolute', top: 6, right: 7, width: 9, height: 9, borderRadius: 5, borderWidth: 1.5 },
  overviewRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.lg },
  overviewPill: { paddingHorizontal: spacing.md, paddingVertical: 6, borderRadius: radii.pill },
  overviewPillText: { color: '#FFFFFF', fontWeight: '700', fontSize: 12 },
});