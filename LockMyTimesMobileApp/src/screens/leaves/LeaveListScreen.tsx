import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchLeaves } from '../../api/endpoints/leaves';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { SegmentedControl } from '../../components/common/SegmentedControl';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { LeavesStackParamList } from '../../navigation/LeavesStack';
import type { LeaveRequestInfo } from '../../api/types';

type Props = NativeStackScreenProps<LeavesStackParamList, 'LeaveList'>;

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
};

const TABS = ['Balances', 'History'] as const;

export function LeaveListScreen({ navigation }: Props) {
  const theme = useTheme();
  const [tab, setTab] = useState<(typeof TABS)[number]>('Balances');

  const { data, isLoading, isRefetching, refetch } = useQuery({
    queryKey: ['leaves', 'index'],
    queryFn: () => fetchLeaves(),
  });

  const counts = useMemo(() => {
    const requests = data?.requests ?? [];
    return {
      pending: requests.filter((r) => r.status === 'pending').length,
      approved: requests.filter((r) => r.status === 'approved').length,
      rejected: requests.filter((r) => r.status === 'rejected').length,
      used: data?.summary.used ?? 0,
    };
  }, [data]);

  function renderRequest({ item, index }: { item: LeaveRequestInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('LeaveDetail', { id: item.id })}
          style={[
            styles.reqCard,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={[styles.typeDot, { backgroundColor: item.leave_type.color }]} />
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
              {item.leave_type.name}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {item.start_date} – {item.end_date} · {item.total_days}d
            </Text>
          </View>
          <StatusBadge value={item.status} color={theme[STATUS_COLOR[item.status]]} filled />
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroRow}>
          <View>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Leave Management</Text>
            <Text style={[typography.body, { color: 'rgba(255,255,255,0.85)', marginTop: 2 }]}>Balances and requests</Text>
          </View>
          <Button title="Apply" variant="accent" icon="add" compact onPress={() => navigation.navigate('LeaveApply')} />
        </View>
      </HeroHeader>

      <View style={styles.padded}>
        <MotiView {...entranceStagger(0)} style={styles.statsRow}>
          <StatCircleTile icon="time" value={counts.pending} label="Pending" color={theme.warning} />
          <StatCircleTile icon="checkmark-done-outline" value={counts.approved} label="Approved" color={theme.success} />
          <StatCircleTile icon="close" value={counts.rejected} label="Rejected" color={theme.danger} />
          <StatCircleTile icon="calendar" value={counts.used} label="Days Used" color={theme.primary} />
        </MotiView>

        <View style={{ marginTop: spacing.md }}>
          <SegmentedControl options={TABS} value={tab} onChange={setTab} />
        </View>
      </View>

      {tab === 'Balances' ? (
        <FlatList
          data={data?.balances ?? []}
          keyExtractor={(b) => String(b.id)}
          contentContainerStyle={styles.padded}
          renderItem={({ item: b, index: i }) => (
            <MotiView {...entranceStagger(i)} style={[styles.balanceCard, { backgroundColor: b.color }]}>
              <View style={styles.balanceRingRow}>
                <ProgressRing
                  percent={b.total > 0 ? (b.available / b.total) * 100 : 0}
                  size={44}
                  strokeWidth={4}
                  gradientColors={['#FFFFFF', 'rgba(255,255,255,0.55)']}
                  trackColor="rgba(255,255,255,0.25)"
                />
                <View style={{ marginLeft: spacing.sm }}>
                  <Text style={[typography.caption, { color: '#fff', opacity: 0.9 }]}>{b.name}</Text>
                  <Text style={[typography.heading, { color: '#fff', marginTop: 2 }]}>{b.available}</Text>
                </View>
              </View>
              <Text style={[typography.caption, { color: '#fff', opacity: 0.8, marginTop: 4 }]}>of {b.total} days left</Text>
            </MotiView>
          )}
          ListEmptyComponent={
            !isLoading ? (
              <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
                No leave balances yet.
              </Text>
            ) : null
          }
        />
      ) : (
        <FlatList
          data={data?.requests ?? []}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderRequest}
          contentContainerStyle={styles.padded}
          refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
          ListEmptyComponent={
            !isLoading ? (
              <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
                No leave requests yet.
              </Text>
            ) : null
          }
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  statsRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg },
  balanceCard: {
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  balanceRingRow: { flexDirection: 'row', alignItems: 'center' },
  reqCard: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
  typeDot: { width: 10, height: 10, borderRadius: 5 },
});
