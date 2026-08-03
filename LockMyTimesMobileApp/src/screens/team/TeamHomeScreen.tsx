import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { AvatarStack } from '../../components/common/AvatarStack';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTeamIndex } from '../../api/endpoints/team';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TeamStackParamList } from '../../navigation/TeamStack';
import type { ExpenseInfo, LeaveRequestInfo, TeamMemberInfo } from '../../api/types';

type Props = NativeStackScreenProps<TeamStackParamList, 'TeamHome'>;

const STATUS_LABEL: Record<string, string> = {
  clocked_in: 'Clocked in',
  on_leave: 'On leave',
  absent: 'Not clocked in',
};

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'textMuted'> = {
  clocked_in: 'success',
  on_leave: 'warning',
  absent: 'textMuted',
};

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

export function TeamHomeScreen({ navigation }: Props) {
  const theme = useTheme();

  const { data, isRefetching, refetch } = useQuery({ queryKey: ['team', 'index'], queryFn: fetchTeamIndex });

  function renderRequesterAvatar(person: { id: number; full_name: string; avatar_url: string } | undefined, size: number) {
    if (!person) {
      return (
        <View
          style={[
            styles.avatarFallback,
            { width: size, height: size, borderRadius: size / 2, backgroundColor: theme.surfaceAlt },
          ]}
        >
          <Icon name="person-outline" size={size * 0.55} color={theme.textMuted} />
        </View>
      );
    }
    return <AvatarStack people={[{ id: person.id, name: person.full_name, avatar_url: person.avatar_url }]} max={1} size={size} />;
  }

  function renderRecentLeave(item: LeaveRequestInfo, index: number) {
    const color = theme[LEAVE_STATUS_COLOR[item.status] ?? 'textMuted'];
    return (
      <MotiView key={item.id} {...entranceStagger(index)}>
        <Pressable onPress={() => navigation.navigate('LeaveApprovals')} style={styles.recentRow}>
          {renderRequesterAvatar(item.employee, 32)}
          <View style={{ flex: 1, marginLeft: spacing.sm }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]} numberOfLines={1}>
              {item.employee?.full_name ?? 'Team member'}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]} numberOfLines={1}>
              {item.leave_type.name} · {item.start_date} – {item.end_date}
            </Text>
          </View>
          <StatusBadge value={item.status} color={color} filled />
        </Pressable>
      </MotiView>
    );
  }

  function renderRecentExpense(item: ExpenseInfo, index: number) {
    const color = theme[EXPENSE_STATUS_COLOR[item.status] ?? 'textMuted'];
    return (
      <MotiView key={item.id} {...entranceStagger(index)}>
        <Pressable onPress={() => navigation.navigate('ExpenseApprovals')} style={styles.recentRow}>
          {renderRequesterAvatar(item.employee, 32)}
          <View style={{ flex: 1, marginLeft: spacing.sm }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]} numberOfLines={1}>
              {item.employee?.full_name ?? 'Team member'}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]} numberOfLines={1}>
              {item.category.name} · {item.expense_date}
            </Text>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {formatCurrency(item.amount, item.currency)}
            </Text>
            <StatusBadge value={item.status} color={color} filled />
          </View>
        </Pressable>
      </MotiView>
    );
  }

  function renderMember({ item, index }: { item: TeamMemberInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('TeamMemberDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.full_name}</Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>{item.position ?? 'No position set'}</Text>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            <StatusBadge
              value={item.status_today}
              label={STATUS_LABEL[item.status_today]}
              color={theme[STATUS_COLOR[item.status_today]]}
              uppercase={false}
              filled
            />
            {(item.pending_leaves > 0 || item.pending_expenses > 0) && (
              <Text style={[typography.caption, { color: theme.warning, marginTop: 4 }]}>
                {item.pending_leaves + item.pending_expenses} pending
              </Text>
            )}
          </View>
        </Pressable>
      </MotiView>
    );
  }

  if (data && !data.is_manager) {
    return (
      <Screen>
        <View style={styles.center}>
          <Text style={[typography.body, { color: theme.textMuted, textAlign: 'center' }]}>
            You don't have any direct reports yet.
          </Text>
        </View>
      </Screen>
    );
  }

  const recentLeaves = data?.recent_leaves ?? [];
  const recentExpenses = data?.recent_expenses ?? [];

  return (
    <Screen padded={false}>
      {/* Everything (hero, stat rows, recent-activity previews, roster header)
          lives in the FlatList's ListHeaderComponent so the whole screen
          scrolls as one unit — a fixed View above the roster FlatList would
          starve the list of space once this header grows this tall. */}
      <FlatList
        style={styles.flex}
        data={data?.reports ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderMember}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListHeaderComponent={
          <>
            <HeroHeader style={styles.heroNegateMargin}>
              <Text style={[typography.title, { color: '#FFFFFF' }]}>My Team</Text>
              <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)', marginTop: 2 }]}>
                {data?.headcount ?? 0} direct report{data?.headcount === 1 ? '' : 's'}
              </Text>
            </HeroHeader>

            <View style={styles.statsRow}>
              <StatCircleTile icon="people-outline" value={data?.headcount ?? 0} label="Headcount" color={theme.categorical[0]} />
              <StatCircleTile icon="checkbox-outline" value={data?.clocked_in ?? 0} label="Working" color={theme.categorical[2]} />
              <StatCircleTile icon="calendar" value={data?.on_leave_count ?? 0} label="On leave" color={theme.categorical[4]} />
            </View>

            <View style={[styles.statsRow, { marginTop: spacing.sm }]}>
              <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('LeaveApprovals')}>
                <StatCircleTile icon="calendar" value={data?.pending_leaves ?? 0} label="Leave approvals" color={theme.primary} />
              </Pressable>
              <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('ExpenseApprovals')}>
                <StatCircleTile icon="receipt-outline" value={data?.pending_expenses ?? 0} label="Expense approvals" color={theme.accentBlue} />
              </Pressable>
            </View>

            <View style={styles.sectionHeaderRow}>
              <Text style={[typography.subheading, { color: theme.text }]}>Recent leaves</Text>
              <Pressable onPress={() => navigation.navigate('LeaveApprovals')}>
                <Text style={[typography.caption, { color: theme.primary, fontWeight: '700' }]}>See all</Text>
              </Pressable>
            </View>
            <View
              style={[
                styles.recentCard,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              {recentLeaves.length === 0 ? (
                <Text style={[typography.caption, { color: theme.textMuted }]}>No recent leave activity.</Text>
              ) : (
                recentLeaves.slice(0, 3).map((item, i) => renderRecentLeave(item, i))
              )}
            </View>

            <View style={styles.sectionHeaderRow}>
              <Text style={[typography.subheading, { color: theme.text }]}>Recent expenses</Text>
              <Pressable onPress={() => navigation.navigate('ExpenseApprovals')}>
                <Text style={[typography.caption, { color: theme.primary, fontWeight: '700' }]}>See all</Text>
              </Pressable>
            </View>
            <View
              style={[
                styles.recentCard,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              {recentExpenses.length === 0 ? (
                <Text style={[typography.caption, { color: theme.textMuted }]}>No recent expense activity.</Text>
              ) : (
                recentExpenses.slice(0, 3).map((item, i) => renderRecentExpense(item, i))
              )}
            </View>

            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Roster</Text>
          </>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24 },
  padded: { paddingHorizontal: 24, paddingBottom: spacing.xl },
  heroNegateMargin: { marginHorizontal: -24 },
  statsRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: spacing.lg,
  },
  recentCard: { borderRadius: radii.lg, padding: spacing.sm, marginTop: spacing.sm },
  recentRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  avatarFallback: { alignItems: 'center', justifyContent: 'center' },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
