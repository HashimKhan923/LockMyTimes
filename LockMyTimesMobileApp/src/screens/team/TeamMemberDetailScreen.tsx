import { useQuery } from '@tanstack/react-query';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTeamMember } from '../../api/endpoints/team';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatTile } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TeamStackParamList } from '../../navigation/TeamStack';

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

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{data.member.full_name}</Text>
        <Text style={[typography.caption, { color: theme.textMuted }]}>
          {data.member.employee_code} · {data.is_on_leave_today ? 'On leave today' : 'Working today'}
        </Text>

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
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Recent leave requests</Text>
          {data.leaves.length === 0 ? (
            <Text style={[typography.caption, { color: theme.textMuted }]}>No leave requests.</Text>
          ) : (
            data.leaves.map((l) => (
              <View key={l.id} style={styles.row}>
                <Text style={[typography.body, { color: theme.text }]}>{l.leave_type.name}</Text>
                <StatusBadge value={l.status} color={theme[LEAVE_STATUS_COLOR[l.status] ?? 'textMuted']} filled />
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
            data.expenses.map((e) => (
              <View key={e.id} style={styles.row}>
                <Text style={[typography.body, { color: theme.text }]}>{e.title}</Text>
                <StatusBadge value={e.status} color={theme[EXPENSE_STATUS_COLOR[e.status] ?? 'textMuted']} filled />
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
  balanceRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: spacing.xs + 2 },
});
