import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { MotiView } from 'moti';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { cancelLoan, fetchLoan } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { GradientCard } from '../../components/common/GradientCard';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { springTransition } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'LoanDetail'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const LOAN_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  active: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
  closed: 'textMuted',
};

const REPAYMENT_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  paid: 'success',
  due: 'warning',
  overdue: 'danger',
};

export function LoanDetailScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;

  const { data, isLoading } = useQuery({ queryKey: ['loans', 'detail', id], queryFn: () => fetchLoan(id) });

  const cancelMutation = useMutation({
    mutationFn: () => cancelLoan(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] });
      navigation.goBack();
    },
  });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const loan = data.loan;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>{loan.loan_number}</Text>
        <Text style={[typography.title, { color: theme.text }]}>{loan.loan_type.name}</Text>

        <MotiView from={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} transition={springTransition}>
          <GradientCard colors={theme.gradients.accent} style={styles.hero}>
            <View style={styles.heroRow}>
              <ProgressRing
                percent={loan.progress_pct}
                size={104}
                strokeWidth={10}
                gradientColors={['#FFFFFF', 'rgba(255,255,255,0.55)']}
                trackColor="rgba(255,255,255,0.25)"
              >
                <Text style={{ color: theme.onPrimary, fontSize: 22, fontWeight: '800' }}>{loan.progress_pct}%</Text>
              </ProgressRing>

              <View style={styles.heroInfo}>
                <StatNumber
                  value={currencyFormatter.format(loan.amount_remaining)}
                  label="Remaining amount"
                  size="lg"
                  color={theme.onPrimary}
                  labelColor="rgba(255,255,255,0.85)"
                />
                <View style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}>
                  <StatusBadge value={loan.status} color={theme.onPrimary} filled />
                </View>
              </View>
            </View>
          </GradientCard>
        </MotiView>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Row label="Principal" value={currencyFormatter.format(loan.principal_amount)} theme={theme} />
          <Row label="EMI" value={currencyFormatter.format(loan.emi_amount)} theme={theme} />
          <Row label="Tenure" value={`${loan.tenure_months} months`} theme={theme} />
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Status</Text>
            <StatusBadge value={loan.status} color={theme[LOAN_STATUS_COLOR[loan.status] ?? 'textMuted']} filled />
          </View>
          {loan.rejection_reason && <Row label="Rejection reason" value={loan.rejection_reason} theme={theme} valueColor={theme.danger} />}
        </View>

        {(loan.repayments?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Repayment schedule</Text>
            {loan.repayments!.slice(0, 6).map((r) => (
              <View key={r.id} style={styles.repayRow}>
                <Text style={[typography.body, { color: theme.text }]}>#{r.installment_number} · {r.due_date}</Text>
                <View style={styles.repayAmount}>
                  <Text style={[typography.caption, { color: theme.textMuted }]}>{currencyFormatter.format(r.emi_amount)}</Text>
                  <StatusBadge value={r.status} color={theme[REPAYMENT_STATUS_COLOR[r.status] ?? 'textMuted']} filled />
                </View>
              </View>
            ))}
          </View>
        )}

        {loan.status === 'pending' && (
          <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
            <Button title="Cancel application" variant="danger" onPress={() => cancelMutation.mutate()} loading={cancelMutation.isPending} />
          </View>
        )}
      </ScrollView>
    </Screen>
  );
}

function Row({ label, value, theme, valueColor }: { label: string; value: string; theme: ReturnType<typeof useTheme>; valueColor?: string }) {
  return (
    <View style={styles.row}>
      <Text style={[typography.caption, { color: theme.textMuted }]}>{label}</Text>
      <Text style={[typography.body, { color: valueColor ?? theme.text }]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  hero: { marginTop: spacing.md },
  heroRow: { flexDirection: 'row', alignItems: 'center' },
  heroInfo: { marginLeft: spacing.lg, flex: 1 },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  row: { marginBottom: spacing.sm },
  repayRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  repayAmount: { alignItems: 'flex-end', gap: 4 },
});
