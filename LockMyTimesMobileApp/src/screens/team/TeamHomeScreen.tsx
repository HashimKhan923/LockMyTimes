import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTeamIndex } from '../../api/endpoints/team';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TeamStackParamList } from '../../navigation/TeamStack';
import type { TeamMemberInfo } from '../../api/types';

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

export function TeamHomeScreen({ navigation }: Props) {
  const theme = useTheme();

  const { data, isRefetching, refetch } = useQuery({ queryKey: ['team', 'index'], queryFn: fetchTeamIndex });

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

  return (
    <Screen padded={false}>
      <HeroHeader>
        <Text style={[typography.title, { color: '#FFFFFF' }]}>My Team</Text>
        <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)', marginTop: 2 }]}>
          {data?.headcount ?? 0} direct report{data?.headcount === 1 ? '' : 's'}
        </Text>
      </HeroHeader>

      <View style={styles.padded}>
        <View style={styles.statsRow}>
          <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('LeaveApprovals')}>
            <StatCircleTile icon="calendar" value={data?.pending_leaves ?? 0} label="Leave approvals" color={theme.primary} />
          </Pressable>
          <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('ExpenseApprovals')}>
            <StatCircleTile icon="receipt-outline" value={data?.pending_expenses ?? 0} label="Expense approvals" color={theme.success} />
          </Pressable>
        </View>

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Roster</Text>
      </View>

      <FlatList
        data={data?.reports ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderMember}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24 },
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  statsRow: { flexDirection: 'row', gap: spacing.sm },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
