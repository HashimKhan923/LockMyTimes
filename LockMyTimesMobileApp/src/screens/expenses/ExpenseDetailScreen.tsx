import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { deleteExpense, fetchExpense } from '../../api/endpoints/expenses';
import { Button } from '../../components/common/Button';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { ExpenseApprovalInfo, TimelineEvent } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseDetail'>;

function formatCurrency(amount: number, currency?: string | null) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(amount);
}

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  paid: 'success',
  submitted: 'warning',
  rejected: 'danger',
  draft: 'textMuted',
  cancelled: 'textMuted',
};

const DECISION_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  rejected: 'danger',
  pending: 'warning',
};

/** Backend timeline events carry a semantic color key ('green'/'red'/'brand'/'gray'), not a hex value — mapped here to the app's real theme colors. */
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

export function ExpenseDetailScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;

  const { data, isLoading } = useQuery({ queryKey: ['expenses', 'detail', id], queryFn: () => fetchExpense(id) });

  const deleteMutation = useMutation({
    mutationFn: () => deleteExpense(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] });
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

  const exp = data.expense;
  const canDelete = exp.status === 'draft' || exp.status === 'submitted';

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>{exp.expense_number}</Text>
        <Text style={[typography.title, { color: theme.text }]}>{exp.title}</Text>
        <Text style={[typography.heading, { color: theme.primary, marginTop: spacing.xs }]}>
          {formatCurrency(exp.amount, exp.currency)}
        </Text>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Category</Text>
            <StatusBadge value={exp.category.name} color={exp.category.color} uppercase={false} filled />
          </View>
          <Row label="Date" value={exp.expense_date} theme={theme} />
          {exp.merchant && <Row label="Merchant" value={exp.merchant} theme={theme} />}
          {exp.payment_method && <Row label="Payment method" value={exp.payment_method} theme={theme} />}
          {exp.project_code && <Row label="Project code" value={exp.project_code} theme={theme} />}
          {exp.is_mileage && (
            <Row
              label="Mileage"
              value={`${exp.miles ?? 0} mi × ${formatCurrency(exp.mileage_rate ?? 0, exp.currency)}/mi`}
              theme={theme}
            />
          )}
          {exp.description && <Row label="Description" value={exp.description} theme={theme} />}
          {exp.submitted_at && <Row label="Submitted" value={new Date(exp.submitted_at).toLocaleString()} theme={theme} />}
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Status</Text>
            <StatusBadge value={exp.status} color={theme[STATUS_COLOR[exp.status] ?? 'textMuted']} filled />
          </View>
          {exp.rejection_reason && (
            <Row label="Rejection reason" value={exp.rejection_reason} theme={theme} valueColor={theme.danger} />
          )}
        </View>

        {exp.receipt_url && (
          <Image source={{ uri: exp.receipt_url }} style={styles.receipt} resizeMode="contain" />
        )}

        {(exp.approvals?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Approval chain</Text>
            {exp.approvals!.map((approval: ExpenseApprovalInfo, i) => (
              <View key={i} style={styles.approvalRow}>
                <View style={{ flex: 1, marginRight: spacing.sm }}>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
                    {approval.approver_name ?? `Level ${approval.level}`}
                  </Text>
                  {approval.comments && (
                    <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>{approval.comments}</Text>
                  )}
                  {approval.decided_at && (
                    <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
                      {new Date(approval.decided_at).toLocaleString()}
                    </Text>
                  )}
                </View>
                <StatusBadge
                  value={approval.decision}
                  color={theme[DECISION_COLOR[approval.decision] ?? 'textMuted']}
                  filled
                />
              </View>
            ))}
          </View>
        )}

        {(exp.timeline?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Timeline</Text>
            {exp.timeline!.map((event: TimelineEvent, i) => {
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

        {canDelete && (
          <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
            <Button title="Delete expense" variant="danger" onPress={() => deleteMutation.mutate()} loading={deleteMutation.isPending} />
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
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  row: { marginBottom: spacing.sm },
  receipt: { width: '100%', height: 220, borderRadius: radii.md, marginTop: spacing.md },
  approvalRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    paddingVertical: spacing.xs + 2,
  },
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: spacing.xs + 2, gap: spacing.sm },
  timelineIconWrap: {
    width: 30,
    height: 30,
    borderRadius: radii.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
