import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchLoansIndex } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { SegmentedControl } from '../../components/common/SegmentedControl';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { LoanInfo, SalaryAdvanceInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'LoanList'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const LOAN_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  active: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
  closed: 'textMuted',
};

const TABS = ['Loans', 'Advances'] as const;

export function LoanListScreen({ navigation }: Props) {
  const theme = useTheme();
  const [tab, setTab] = useState<(typeof TABS)[number]>('Loans');

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['loans', 'index'],
    queryFn: fetchLoansIndex,
  });

  function renderLoan({ item, index }: { item: LoanInfo; index: number }) {
    const totalInstallments = item.installments_paid + item.installments_remaining;
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('LoanDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={styles.cardTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.loan_type.name}</Text>
              <Text style={[typography.caption, { color: theme.textMuted }]}>{item.loan_number}</Text>
            </View>
            <View style={{ alignItems: 'flex-end' }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
                {currencyFormatter.format(item.principal_amount)}
              </Text>
              <StatusBadge value={item.status} color={theme[LOAN_STATUS_COLOR[item.status] ?? 'textMuted']} filled />
            </View>
          </View>

          <View style={[styles.progressTrack, { backgroundColor: theme.surfaceAlt }]}>
            <View style={[styles.progressFill, { width: `${Math.max(4, item.progress_pct)}%`, backgroundColor: theme.primary }]} />
          </View>
          {totalInstallments > 0 && (
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
              {item.installments_paid} of {totalInstallments} installments paid
            </Text>
          )}
        </Pressable>
      </MotiView>
    );
  }

  function renderAdvance({ item, index }: { item: SalaryAdvanceInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('AdvanceDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={styles.cardTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.advance_number}</Text>
              <Text style={[typography.caption, { color: theme.textMuted }]} numberOfLines={1}>
                {item.reason}
              </Text>
            </View>
            <View style={{ alignItems: 'flex-end' }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
                {currencyFormatter.format(item.amount)}
              </Text>
              <StatusBadge value={item.status} color={theme[LOAN_STATUS_COLOR[item.status] ?? 'textMuted']} filled />
            </View>
          </View>

          <View style={[styles.progressTrack, { backgroundColor: theme.surfaceAlt }]}>
            <View style={[styles.progressFill, { width: `${Math.max(4, item.progress_pct)}%`, backgroundColor: theme.primary }]} />
          </View>
          {item.installments_count > 0 && (
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
              {item.installments_paid} of {item.installments_count} installments paid
            </Text>
          )}
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <Text style={[typography.title, { color: '#FFFFFF' }]}>Loans & Advances</Text>

        {data?.active_loan ? (
          <View style={{ marginTop: spacing.md }}>
            <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)' }]}>
              Active loan · {data.active_loan.loan_type.name}
            </Text>
            <View style={styles.heroRow}>
              <ProgressRing
                percent={data.active_loan.progress_pct}
                size={76}
                strokeWidth={7}
                gradientColors={['#FFFFFF', 'rgba(255,255,255,0.55)']}
                trackColor="rgba(255,255,255,0.25)"
              >
                <Text style={{ color: '#FFFFFF', fontWeight: '800' }}>{data.active_loan.progress_pct}%</Text>
              </ProgressRing>
              <StatNumber
                value={currencyFormatter.format(data.active_loan.amount_remaining)}
                label="Remaining"
                size="lg"
                color="#FFFFFF"
                labelColor="rgba(255,255,255,0.85)"
                style={{ marginLeft: spacing.lg }}
              />
            </View>
            {data.next_emi && (
              <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)', marginTop: spacing.sm }]}>
                Next EMI {currencyFormatter.format(data.next_emi.emi_amount)} due {data.next_emi.due_date}
              </Text>
            )}
          </View>
        ) : (
          <Text style={[typography.body, { color: 'rgba(255,255,255,0.85)', marginTop: 4 }]}>
            No active loan right now
          </Text>
        )}
      </HeroHeader>

      <View style={styles.padded}>
        <SegmentedControl options={TABS} value={tab} onChange={setTab} />

        {tab === 'Loans' && data?.loan_stats ? (
          <MotiView {...entranceStagger(0)} style={styles.statsRow}>
            <StatCircleTile icon="time" value={data.loan_stats.pending} label="Pending" color={theme.warning} />
            <StatCircleTile icon="cash-outline" value={data.loan_stats.active} label="Active" color={theme.success} />
            <StatCircleTile icon="checkmark-done-outline" value={data.loan_stats.completed} label="Completed" color={theme.success} />
          </MotiView>
        ) : null}

        {tab === 'Advances' && data?.advance_stats ? (
          <MotiView {...entranceStagger(0)} style={styles.statsRow}>
            <StatCircleTile icon="time" value={data.advance_stats.pending} label="Pending" color={theme.warning} />
            <StatCircleTile icon="cash-outline" value={data.advance_stats.active} label="Active" color={theme.success} />
            <StatCircleTile icon="checkmark-done-outline" value={data.advance_stats.completed} label="Completed" color={theme.success} />
          </MotiView>
        ) : null}

        <View style={{ marginTop: spacing.md }}>
          <Button
            title={tab === 'Loans' ? 'Apply for a loan' : 'Request an advance'}
            onPress={() => navigation.navigate(tab === 'Loans' ? 'LoanApply' : 'AdvanceApply')}
          />
        </View>
      </View>

      {tab === 'Loans' ? (
        <FlatList
          data={data?.loans ?? []}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderLoan}
          contentContainerStyle={styles.padded}
          refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
          ListEmptyComponent={<Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>No loans yet.</Text>}
        />
      ) : (
        <FlatList
          data={data?.advances ?? []}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderAdvance}
          contentContainerStyle={styles.padded}
          refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
          ListEmptyComponent={<Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>No advances yet.</Text>}
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  heroRow: { flexDirection: 'row', alignItems: 'center', marginTop: spacing.sm },
  statsRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
  cardTopRow: { flexDirection: 'row', alignItems: 'center' },
  progressTrack: { height: 6, borderRadius: 3, marginTop: spacing.sm, overflow: 'hidden' },
  progressFill: { height: 6, borderRadius: 3 },
});
