import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { deleteExpense, fetchExpense } from '../../api/endpoints/expenses';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseDetail'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  paid: 'success',
  submitted: 'warning',
  rejected: 'danger',
  draft: 'textMuted',
  cancelled: 'textMuted',
};

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
          {currencyFormatter.format(exp.amount)}
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

        {exp.timeline && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Timeline</Text>
            {exp.timeline.map((event, i) => (
              <View key={i} style={styles.timelineRow}>
                <Text style={[typography.body, { color: theme.text }]}>{event.title}</Text>
                <Text style={[typography.caption, { color: theme.textMuted }]}>
                  {new Date(event.when).toLocaleString()}
                </Text>
              </View>
            ))}
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
  timelineRow: { paddingVertical: spacing.xs + 2 },
});
