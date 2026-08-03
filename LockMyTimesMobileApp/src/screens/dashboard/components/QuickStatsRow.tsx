import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { UseQueryResult } from '@tanstack/react-query';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { Icon } from '../../../components/common/Icon';
import { SkeletonBlock } from '../../../components/common/SkeletonBlock';
import { entranceStagger } from '../../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import type { MainTabsParamList } from '../../../navigation/MainTabs';
import type { AttendanceIndexResponse, AnnouncementsIndexResponse, LeaveIndexResponse, TasksIndexResponse } from '../../../api/types';

function Tile({
  index,
  label,
  value,
  color,
  icon,
  isLoading,
  onPress,
}: {
  index: number;
  label: string;
  value: string | number | undefined;
  color: string;
  icon: string;
  isLoading: boolean;
  onPress: () => void;
}) {
  const theme = useTheme();

  if (isLoading) {
    return <SkeletonBlock width={140} height={96} radius={radii.xl} style={styles.tile} />;
  }

  return (
    <MotiView {...entranceStagger(index)} style={styles.tile}>
      <Pressable
        onPress={onPress}
        style={[
          styles.tileCard,
          { backgroundColor: theme.surface },
          Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
        ]}
      >
        <View style={[styles.iconBadge, { backgroundColor: color + '1F' }]}>
          <Icon name={icon} size={18} color={color} />
        </View>
        <Text style={[typography.heading, { color: theme.text, marginTop: spacing.sm }]}>{value ?? '—'}</Text>
        <Text style={[typography.caption, { color: theme.textMuted }]}>{label}</Text>
      </Pressable>
    </MotiView>
  );
}

export function QuickStatsRow({
  indexQuery,
  leavesQuery,
  tasksQuery,
  announcementsQuery,
  navigation,
}: {
  indexQuery: UseQueryResult<AttendanceIndexResponse>;
  leavesQuery: UseQueryResult<LeaveIndexResponse>;
  tasksQuery: UseQueryResult<TasksIndexResponse>;
  announcementsQuery: UseQueryResult<AnnouncementsIndexResponse>;
  navigation: BottomTabNavigationProp<MainTabsParamList, 'Dashboard'>;
}) {
  const theme = useTheme();

  return (
    <View style={styles.row}>
      <Tile
        index={0}
        label="Present days"
        value={indexQuery.data?.summary.present_days}
        color={theme.categorical[0]}
        icon="checkmark-done-outline"
        isLoading={indexQuery.isLoading}
        onPress={() => navigation.navigate('Attendance', { screen: 'AttendanceHome' })}
      />
      <Tile
        index={1}
        label="Open tasks"
        value={tasksQuery.data?.counters.open_count}
        color={theme.primary}
        icon="checkbox-outline"
        isLoading={tasksQuery.isLoading}
        onPress={() => navigation.navigate('Tasks', { screen: 'TaskList' })}
      />
      <Tile
        index={2}
        label="Leave days left"
        value={leavesQuery.data?.summary.available}
        color={theme.categorical[4]}
        icon="calendar-outline"
        isLoading={leavesQuery.isLoading}
        onPress={() => navigation.navigate('Leaves', { screen: 'LeaveList' })}
      />
      <Tile
        index={3}
        label="Unread updates"
        value={announcementsQuery.data?.counters.unread}
        color={theme.categorical[3]}
        icon="megaphone-outline"
        isLoading={announcementsQuery.isLoading}
        onPress={() => navigation.navigate('More', { screen: 'Announcements', params: { screen: 'AnnouncementList' } })}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  tile: { minWidth: 140, flexGrow: 1 },
  tileCard: { borderRadius: radii.xl, padding: spacing.md },
  iconBadge: { width: 36, height: 36, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
});
