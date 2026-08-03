import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { MotiView } from 'moti';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { cancelLoan, fetchLoan } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { GradientCard } from '../../components/common/GradientCard';
import { Icon } from '../../components/common/Icon';
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

/** Backend timeline events carry a semantic color key ('green'/'red'/'brand'/'gray'), not a hex value — mapped here to the app's real theme colors. Icon keys (file-plus/send/check-circle/x-circle/banknote/flag/ban) map directly onto Icon's MAP. */
function timelineColor(theme: ReturnType<typeof useTheme>, key: string): string {
  switch (key) {
    case 'green':
      return theme.success;
    case 'red':
      return theme.danger;
    case 'brand':
      return theme.primary;
    default:
      return theme.textMuted;
  }
}

function formatLabel(value: string): string {
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

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
          {loan.interest_type && <Row label="Interest type" value={formatLabel(loan.interest_type)} theme={theme} />}
          <Row label="EMI" value={currencyFormatter.format(loan.emi_amount)} theme={theme} />
          <Row label="Tenure" value={`${loan.tenure_months} months`} theme={theme} />
          <Row label="Processing fee" value={currencyFormatter.format(loan.processing_fee)} theme={theme} />
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Auto-deduct from payroll</Text>
            <StatusBadge
              value={loan.auto_deduct_from_payroll ? 'enabled' : 'disabled'}
              label={loan.auto_deduct_from_payroll ? 'Enabled' : 'Disabled'}
              color={loan.auto_deduct_from_payroll ? theme.accentBlue : theme.textMuted}
              filled
            />
          </View>
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Status</Text>
            <StatusBadge value={loan.status} color={theme[LOAN_STATUS_COLOR[loan.status] ?? 'textMuted']} filled />
          </View>
          {loan.rejection_reason && <Row label="Rejection reason" value={loan.rejection_reason} theme={theme} valueColor={theme.danger} />}
        </View>

        {(loan.guarantor_name || loan.guarantor_phone) && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Guarantor</Text>
            {loan.guarantor_name && <Row label="Name" value={loan.guarantor_name} theme={theme} />}
            {loan.guarantor_phone && <Row label="Phone" value={loan.guarantor_phone} theme={theme} />}
          </View>
        )}

        {(loan.timeline?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Timeline</Text>
            {loan.timeline!.map((event, i) => {
              const color = timelineColor(theme, event.color);
              return (
                <View key={i} style={styles.timelineRow}>
                  <View style={[styles.timelineIconWrap, { backgroundColor: color + '22' }]}>
                    <Icon name={event.icon} size={16} color={color} weight="bold" />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{event.title}</Text>
                    {event.detail && (
                      <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>{event.detail}</Text>
                    )}
                    <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
                      {new Date(event.when).toLocaleString()}
                    </Text>
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {(loan.repayments?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Repayment schedule</Text>
            {loan.repayments!.map((r, i) => (
              <View
                key={r.id}
                style={[styles.repayRow, i > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.border }]}
              >
                <View style={{ flex: 1 }}>
                  <Text style={[typography.body, { color: r.is_overdue ? theme.danger : theme.text }]}>
                    #{r.installment_number} · {r.due_date}
                  </Text>
                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
                    Principal {currencyFormatter.format(r.principal_component)} · Interest {currencyFormatter.format(r.interest_component)}
                  </Text>
                </View>
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
  repayRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.sm },
  repayAmount: { alignItems: 'flex-end', gap: 4 },
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: spacing.xs + 2, gap: spacing.sm },
  timelineIconWrap: { width: 30, height: 30, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
});
