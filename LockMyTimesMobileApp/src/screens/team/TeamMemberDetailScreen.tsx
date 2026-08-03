import { useQuery } from '@tanstack/react-query';
import { Linking, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTeamMember } from '../../api/endpoints/team';
import { Icon } from '../../components/common/Icon';
import { IconInfoCard, IconInfoRow } from '../../components/common/IconInfoRow';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatTile } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TeamStackParamList } from '../../navigation/TeamStack';
import type { AttendanceRecord, TaskInfo } from '../../api/types';
import { PRIORITY_META } from '../tasks/taskDisplay';

type Props = NativeStackScreenProps<TeamStackParamList, 'TeamMemberDetail'>;

const LEAVE_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
};

const EXPENSE_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  paid: 'success',
  submitted: 'warning',
  rejected: 'danger',
  draft: 'textMuted',
  cancelled: 'textMuted',
};

function formatCurrency(amount: number, currency?: string | null) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(amount);
}

function formatDate(value: string | null) {
  if (!value) return null;
  return new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatShortDate(value: string) {
  return new Date(value + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

export function TeamMemberDetailScreen({ route }: Props) {
  const theme = useTheme();
  const { id } = route.params;

  const { data, isLoading } = useQuery({ queryKey: ['team', 'member', id], queryFn: () => fetchTeamMember(id) });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const member = data.member;

  function attendanceColor(rec: AttendanceRecord) {
    if (rec.is_late) return theme.warning;
    if (rec.status === 'absent') return theme.danger;
    if (rec.status === 'on_leave' || rec.status === 'holiday') return theme.primary;
    if (rec.total_hours > 0) return theme.success;
    return theme.border;
  }

  function renderTask(task: TaskInfo, index: number) {
    const priority = PRIORITY_META[task.priority];
    return (
      <View
        key={task.id}
        style={[styles.taskRow, index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.border }]}
      >
        <View style={{ flex: 1 }}>
          <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]} numberOfLines={1}>
            {task.title}
          </Text>
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]} numberOfLines={1}>
            {task.project?.name ?? task.task_code}
          </Text>
        </View>
        <View style={{ alignItems: 'flex-end' }}>
          <StatusBadge value={task.priority} label={priority.label} color={theme[priority.colorKey]} filled uppercase={false} />
          <View style={styles.dueRow}>
            <Icon name="calendar" size={12} color={task.is_overdue ? theme.danger : theme.textMuted} />
            <Text
              style={[
                typography.caption,
                { color: task.is_overdue ? theme.danger : theme.textMuted, marginLeft: 4, fontWeight: task.is_overdue ? '700' : '400' },
              ]}
            >
              {task.due_date ? formatDate(task.due_date) : 'No due date'}
            </Text>
          </View>
        </View>
      </View>
    );
  }

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{member.full_name}</Text>
        <Text style={[typography.caption, { color: theme.textMuted }]}>
          {member.employee_code} · {data.is_on_leave_today ? 'On leave today' : 'Working today'}
        </Text>

        <View style={[styles.card, { backgroundColor: theme.surface, marginTop: spacing.md, padding: 0 }, Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android]}>
          <IconInfoCard>
            {member.position && <IconInfoRow icon="briefcase" label="Position" value={member.position} />}
            {member.department && <IconInfoRow icon="business" label="Department" value={member.department} />}
            {member.location && <IconInfoRow icon="map-pin" label="Location" value={member.location} />}
            {member.email && <IconInfoRow icon="mail" label="Email" value={member.email} />}
            {member.phone && <IconInfoRow icon="call-outline" label="Phone" value={member.phone} />}
            {member.hire_date && <IconInfoRow icon="calendar" label="Hire date" value={formatDate(member.hire_date) ?? member.hire_date} last />}
          </IconInfoCard>
        </View>

        <View style={styles.statsRow}>
          <StatTile label="Pending leaves" value={data.pending_leaves} />
          <StatTile label="Pending expenses" value={data.pending_expenses} />
          <StatTile label="Open tasks" value={data.open_tasks.length} />
        </View>

        {data.leave_balances.length > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Leave balances</Text>
            {data.leave_balances.map((b, i) => (
              <View key={i} style={styles.balanceRow}>
                <ProgressRing percent={b.total > 0 ? (b.available / b.total) * 100 : 0} size={36} strokeWidth={4} />
                <Text style={[typography.body, { color: theme.text, marginLeft: spacing.sm, flex: 1 }]}>{b.name}</Text>
                <Text style={[typography.caption, { color: theme.textMuted }]}>{b.available} / {b.total} days</Text>
              </View>
            ))}
          </View>
        )}

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Open tasks</Text>
          {data.open_tasks.length === 0 ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>No open tasks.</Text>
          ) : (
            data.open_tasks.map((task, i) => renderTask(task, i))
          )}
        </View>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Last 14 days</Text>
          {data.recent_attendance.length === 0 ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>No attendance recorded yet.</Text>
          ) : (
            <>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.attendanceRow}>
                {data.recent_attendance.map((rec) => (
                  <View key={rec.id} style={[styles.attendanceCell, { backgroundColor: theme.surfaceAlt }]}>
                    <Text style={[styles.attendanceWeekday, { color: theme.textMuted }]}>
                      {formatShortDate(rec.work_date).slice(0, 3)}
                    </Text>
                    <View style={[styles.attendanceDot, { backgroundColor: attendanceColor(rec) }]} />
                    <Text style={[styles.attendanceHours, { color: theme.text }]}>
                      {rec.total_hours > 0 ? `${rec.total_hours}h` : '–'}
                    </Text>
                  </View>
                ))}
              </ScrollView>
              <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.sm }]}>
                {data.recent_attendance.reduce((sum, r) => sum + r.total_hours, 0).toFixed(1)}h worked over the last{' '}
                {data.recent_attendance.length} days
              </Text>
            </>
          )}
        </View>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Recent leave requests</Text>
          {data.leaves.length === 0 ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>No leave requests.</Text>
          ) : (
            data.leaves.map((l, i) => (
              <View key={l.id} style={[styles.detailRow, i > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.border }]}>
                <View style={styles.headerRow}>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{l.leave_type.name}</Text>
                  <StatusBadge value={l.status} color={theme[LEAVE_STATUS_COLOR[l.status] ?? 'textMuted']} filled />
                </View>
                <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
                  {l.start_date} – {l.end_date} · {l.total_days} day{l.total_days === 1 ? '' : 's'}
                </Text>
                {l.rejection_reason && (
                  <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.xs }]}>
                    Rejected: {l.rejection_reason}
                  </Text>
                )}
                {l.approver_comments && (
                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
                    {l.approver_comments}
                  </Text>
                )}
              </View>
            ))
          )}
        </View>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Recent expenses</Text>
          {data.expenses.length === 0 ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>No expenses.</Text>
          ) : (
            data.expenses.map((e, i) => (
              <View key={e.id} style={[styles.detailRow, i > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.border }]}>
                <View style={styles.headerRow}>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{e.title}</Text>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>{formatCurrency(e.amount, e.currency)}</Text>
                </View>
                <View style={styles.categoryRow}>
                  <StatusBadge value={e.category.name} color={e.category.color} uppercase={false} filled />
                  <Text style={[typography.caption, { color: theme.textMuted }]}>{e.expense_date}</Text>
                  <StatusBadge value={e.status} color={theme[EXPENSE_STATUS_COLOR[e.status] ?? 'textMuted']} filled />
                </View>
                {e.merchant && (
                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>{e.merchant}</Text>
                )}
                {e.rejection_reason && (
                  <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.xs }]}>
                    Rejected: {e.rejection_reason}
                  </Text>
                )}
                {e.receipt_url && (
                  <Pressable onPress={() => Linking.openURL(e.receipt_url!)} style={styles.receiptLink}>
                    <Icon name="receipt-outline" size={14} color={theme.primary} />
                    <Text style={[typography.caption, { color: theme.primary, fontWeight: '700', marginLeft: 4 }]}>View receipt</Text>
                  </Pressable>
                )}
              </View>
            ))
          )}
        </View>

        <View style={{ height: spacing.xl }} />
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  statsRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  detailRow: { paddingVertical: spacing.sm },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  categoryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: 4 },
  balanceRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  taskRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingVertical: spacing.sm,
  },
  dueRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4 },
  attendanceRow: { gap: spacing.sm, paddingVertical: spacing.xs },
  attendanceCell: { width: 56, borderRadius: radii.md, paddingVertical: spacing.sm, alignItems: 'center', gap: 6 },
  attendanceWeekday: { fontSize: 11, fontWeight: '600', textTransform: 'uppercase' },
  attendanceDot: { width: 10, height: 10, borderRadius: 5 },
  attendanceHours: { fontSize: 12, fontWeight: '700' },
  receiptLink: { flexDirection: 'row', alignItems: 'center', marginTop: spacing.xs },
});
