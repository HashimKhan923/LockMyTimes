import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { UseQueryResult } from '@tanstack/react-query';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { SkeletonList } from '../../../components/common/SkeletonBlock';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { entranceStagger } from '../../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import type { MainTabsParamList } from '../../../navigation/MainTabs';
import type { LeaveIndexResponse, PayslipIndexResponse, TaskInfo, TasksIndexResponse } from '../../../api/types';

const PRIORITY_COLOR: Record<string, 'textMuted' | 'primary' | 'warning' | 'danger'> = {
  low: 'textMuted',
  normal: 'primary',
  high: 'warning',
  urgent: 'danger',
};

function SectionHeader({ title, onSeeAll }: { title: string; onSeeAll: () => void }) {
  const theme = useTheme();
  return (
    <View style={styles.sectionHeader}>
      <Text style={[typography.subheading, { color: theme.text }]}>{title}</Text>
      <Pressable onPress={onSeeAll}>
        <Text style={[typography.caption, { color: theme.primary, fontWeight: '600' }]}>See all</Text>
      </Pressable>
    </View>
  );
}

function EmptyRow({ text }: { text: string }) {
  const theme = useTheme();
  return (
    <View style={[styles.emptyRow, { backgroundColor: theme.surface }]}>
      <Text style={[typography.caption, { color: theme.textMuted }]}>{text}</Text>
    </View>
  );
}

export function PendingItemsCard({
  leavesQuery,
  tasksQuery,
  payslipsQuery,
  navigation,
}: {
  leavesQuery: UseQueryResult<LeaveIndexResponse>;
  tasksQuery: UseQueryResult<TasksIndexResponse>;
  payslipsQuery: UseQueryResult<PayslipIndexResponse>;
  navigation: BottomTabNavigationProp<MainTabsParamList, 'Dashboard'>;
}) {
  const theme = useTheme();

  const pendingLeaves = leavesQuery.data?.requests.filter((r) => r.status === 'pending').slice(0, 3) ?? [];

  const actionTasks = [...(tasksQuery.data?.tasks ?? [])]
    .sort((a, b) => {
      if (a.is_overdue !== b.is_overdue) return a.is_overdue ? -1 : 1;
      if (!a.due_date) return 1;
      if (!b.due_date) return -1;
      return new Date(a.due_date).getTime() - new Date(b.due_date).getTime();
    })
    .slice(0, 3);

  const latestPayslip = payslipsQuery.data?.latest ?? null;

  function renderTask(item: TaskInfo, index: number) {
    return (
      <MotiView key={item.id} {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('Tasks', { screen: 'TaskDetail', params: { id: item.id } })}
          style={[
            styles.row,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>{item.task_code}</Text>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]} numberOfLines={1}>
              {item.title}
            </Text>
          </View>
          <Text style={[typography.caption, { color: theme[PRIORITY_COLOR[item.priority]], fontWeight: '700' }]}>
            {item.priority.toUpperCase()}
          </Text>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <View>
      <SectionHeader title="Pending leaves" onSeeAll={() => navigation.navigate('Leaves', { screen: 'LeaveList' })} />
      {leavesQuery.isLoading ? (
        <SkeletonList rows={2} rowHeight={56} />
      ) : pendingLeaves.length === 0 ? (
        <EmptyRow text="No pending leave requests." />
      ) : (
        pendingLeaves.map((item, index) => (
          <MotiView key={item.id} {...entranceStagger(index)}>
            <Pressable
              onPress={() => navigation.navigate('Leaves', { screen: 'LeaveDetail', params: { id: item.id } })}
              style={[
            styles.row,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
            >
              <View style={[styles.dot, { backgroundColor: item.leave_type.color }]} />
              <View style={{ flex: 1 }}>
                <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.leave_type.name}</Text>
                <Text style={[typography.caption, { color: theme.textMuted }]}>
                  {item.start_date} – {item.end_date} · {item.total_days}d
                </Text>
              </View>
              <StatusBadge value="pending" color={theme.warning} />
            </Pressable>
          </MotiView>
        ))
      )}

      <SectionHeader title="Tasks needing action" onSeeAll={() => navigation.navigate('Tasks', { screen: 'TaskList' })} />
      {tasksQuery.isLoading ? (
        <SkeletonList rows={2} rowHeight={56} />
      ) : actionTasks.length === 0 ? (
        <EmptyRow text="You're all caught up 🎉" />
      ) : (
        actionTasks.map((item, index) => renderTask(item, index))
      )}

      <SectionHeader title="Latest payslip" onSeeAll={() => navigation.navigate('More', { screen: 'PayslipList' })} />
      {payslipsQuery.isLoading ? (
        <SkeletonList rows={1} rowHeight={56} />
      ) : !latestPayslip ? (
        <EmptyRow text="No payslips yet." />
      ) : (
        <MotiView {...entranceStagger(0)}>
          <Pressable
            onPress={() => navigation.navigate('More', { screen: 'PayslipDetail', params: { id: latestPayslip.id } })}
            style={[
            styles.row,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
          >
            <View style={{ flex: 1 }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
                {latestPayslip.payslip_number}
              </Text>
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                {latestPayslip.period_start} – {latestPayslip.period_end}
              </Text>
            </View>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {latestPayslip.net_pay}
            </Text>
          </Pressable>
        </MotiView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: spacing.lg,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
  emptyRow: {
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  dot: { width: 10, height: 10, borderRadius: 5 },
});
