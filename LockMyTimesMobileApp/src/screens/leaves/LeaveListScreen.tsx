import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchLeaves } from '../../api/endpoints/leaves';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { SegmentedControl } from '../../components/common/SegmentedControl';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { LeavesStackParamList } from '../../navigation/LeavesStack';
import { useResetOnTabBlur } from '../../navigation/useResetOnTabBlur';
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
  useResetOnTabBlur(navigation);
  const theme = useTheme();
  const [tab, setTab] = useState<(typeof TABS)[number]>('Balances');
  const [selectedYear, setSelectedYear] = useState<number | undefined>(undefined);

  const { data, isLoading, isRefetching, refetch } = useQuery({
    queryKey: ['leaves', 'index', selectedYear],
    queryFn: () => fetchLeaves(selectedYear),
  });

  const counts = useMemo(() => {
    const requests = data?.requests ?? [];
    return {
      pending: requests.filter((r) => r.status === 'pending').length,
      approved: requests.filter((r) => r.status === 'approved').length,
      rejected: requests.filter((r) => r.status === 'rejected').length,
    };
  }, [data]);

  const summary = data?.summary;
  const totalForBar = Math.max(1, summary?.total ?? 0);
  const usedPct = summary ? Math.round((summary.used / totalForBar) * 100) : 0;
  const pendingPct = summary ? Math.round((summary.pending / totalForBar) * 100) : 0;

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
              {item.day_part && item.day_part !== 'full' ? ` · ${item.day_part.replace('_', ' ')}` : ''}
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
          <View style={styles.heroTitleCol}>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Leaves</Text>
            {summary && (
              <Text style={[typography.body, { color: 'rgba(255,255,255,0.9)', marginTop: 2 }]}>
                {summary.available} day{summary.available === 1 ? '' : 's'} available · {summary.used} used · {summary.pending} pending
              </Text>
            )}
          </View>
          <Button title="Apply" variant="accent" icon="add" compact onPress={() => navigation.navigate('LeaveApply')} />
        </View>

        {summary && summary.total > 0 && (
          <View style={styles.miniBarWrap}>
            <View style={styles.miniBarTrack}>
              <View style={[styles.miniBarFill, { width: `${usedPct}%`, backgroundColor: '#FFFFFF' }]} />
              <View style={[styles.miniBarFill, { width: `${pendingPct}%`, backgroundColor: theme.warning }]} />
            </View>
          </View>
        )}
      </HeroHeader>

      <View style={styles.padded}>
        {(data?.years.length ?? 0) > 1 && (
          <FlatList
            horizontal
            showsHorizontalScrollIndicator={false}
            data={data!.years}
            keyExtractor={(y) => String(y)}
            style={{ marginTop: spacing.md }}
            contentContainerStyle={{ gap: spacing.sm }}
            renderItem={({ item: y }) => {
              const active = (selectedYear ?? data?.year) === y;
              return (
                <Pressable
                  onPress={() => setSelectedYear(y)}
                  style={[
                    styles.yearChip,
                    {
                      backgroundColor: active ? theme.primary : theme.surface,
                      borderColor: active ? theme.primary : theme.border,
                    },
                  ]}
                >
                  <Text style={{ color: active ? theme.onPrimary : theme.text, fontWeight: '700' }}>{y}</Text>
                </Pressable>
              );
            }}
          />
        )}

        <MotiView {...entranceStagger(0)} style={styles.statsRow}>
          <StatCircleTile icon="time" value={counts.pending} label="Pending" color={theme.warning} />
          <StatCircleTile icon="checkmark-done-outline" value={counts.approved} label="Approved" color={theme.success} />
          <StatCircleTile icon="close" value={counts.rejected} label="Rejected" color={theme.danger} />
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
          refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
          renderItem={({ item: b, index: i }) => (
            <MotiView {...entranceStagger(i)} style={[styles.balanceCard, { backgroundColor: b.color }]}>
              <View style={styles.balanceTopRow}>
                <View style={styles.balanceRingRow}>
                  <ProgressRing
                    percent={b.total > 0 ? (b.available / b.total) * 100 : 0}
                    size={44}
                    strokeWidth={4}
                    gradientColors={['#FFFFFF', 'rgba(255,255,255,0.55)']}
                    trackColor="rgba(255,255,255,0.25)"
                  />
                  <View style={{ marginLeft: spacing.sm }}>
                    <View style={styles.balanceNameRow}>
                      <Text style={[typography.caption, { color: '#fff', opacity: 0.9 }]}>{b.name}</Text>
                      {!b.is_paid && (
                        <View style={styles.unpaidBadge}>
                          <Text style={styles.unpaidBadgeText}>UNPAID</Text>
                        </View>
                      )}
                    </View>
                    <Text style={[typography.heading, { color: '#fff', marginTop: 2 }]}>
                      {b.available} <Text style={{ fontSize: 14, opacity: 0.8 }}>/ {b.total}</Text>
                    </Text>
                  </View>
                </View>
              </View>
              <Text style={[typography.caption, { color: '#fff', opacity: 0.8, marginTop: spacing.sm }]}>days available</Text>
              <View style={styles.balanceFooterRow}>
                <Text style={[typography.caption, { color: '#fff', opacity: 0.85, fontWeight: '600' }]}>{b.used} used</Text>
                {b.pending > 0 ? (
                  <View style={styles.pendingTag}>
                    <Icon name="time" size={11} color="#fff" />
                    <Text style={[typography.caption, { color: '#fff', fontWeight: '700', marginLeft: 3 }]}>{b.pending} pending</Text>
                  </View>
                ) : (
                  <Text style={[typography.caption, { color: '#fff', opacity: 0.85, fontWeight: '600' }]}>{b.used_pct}% used</Text>
                )}
              </View>
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
  heroTitleCol: { flex: 1, marginRight: spacing.sm },
  miniBarWrap: { marginTop: spacing.md },
  miniBarTrack: {
    flexDirection: 'row',
    height: 6,
    borderRadius: 3,
    overflow: 'hidden',
    backgroundColor: 'rgba(255,255,255,0.25)',
  },
  miniBarFill: { height: 6 },
  yearChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm - 2, borderRadius: radii.pill, borderWidth: 1 },
  statsRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  balanceCard: {
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  balanceTopRow: { flexDirection: 'row', alignItems: 'center' },
  balanceRingRow: { flexDirection: 'row', alignItems: 'center', flex: 1 },
  balanceNameRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  unpaidBadge: { backgroundColor: 'rgba(255,255,255,0.25)', paddingHorizontal: 5, paddingVertical: 1, borderRadius: radii.sm },
  unpaidBadgeText: { fontSize: 8, fontWeight: '900', color: '#fff' },
  balanceFooterRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.xs },
  pendingTag: { flexDirection: 'row', alignItems: 'center' },
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
