import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Linking, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { cancelLeave, fetchLeave } from '../../api/endpoints/leaves';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { LeavesStackParamList } from '../../navigation/LeavesStack';

type Props = NativeStackScreenProps<LeavesStackParamList, 'LeaveDetail'>;

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
};

export function LeaveDetailScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;

  const { data, isLoading } = useQuery({
    queryKey: ['leaves', 'detail', id],
    queryFn: () => fetchLeave(id),
  });

  const cancelMutation = useMutation({
    mutationFn: () => cancelLeave(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leaves'] });
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

  const lr = data.leave;
  const canCancel = lr.status === 'pending' || lr.status === 'approved';

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <View style={styles.headerRow}>
          <View style={[styles.dot, { backgroundColor: lr.leave_type.color }]} />
          <Text style={[typography.title, { color: theme.text }]}>{lr.leave_type.name}</Text>
        </View>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: 4 }]}>{lr.request_number}</Text>

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <View style={styles.heroRow}>
            <StatNumber value={lr.total_days} label="Total days" size="lg" />
            <StatusBadge value={lr.status} color={theme[STATUS_COLOR[lr.status] ?? 'textMuted']} filled />
          </View>
          <Row label="Dates" value={`${lr.start_date} – ${lr.end_date}`} theme={theme} />
          <Row label="Reason" value={lr.reason} theme={theme} />
          {lr.contact_during_leave && (
            <Row label="Contact" value={lr.contact_during_leave} theme={theme} />
          )}
          {lr.approver_name && <Row label="Reviewed by" value={lr.approver_name} theme={theme} />}
          {lr.rejection_reason && (
            <Row label="Rejection reason" value={lr.rejection_reason} theme={theme} valueColor={theme.danger} />
          )}
        </View>

        {lr.attachments.length > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Attachments</Text>
            {lr.attachments.map((a, i) => (
              <Text
                key={i}
                style={[typography.body, { color: theme.primary, marginTop: 4 }]}
                onPress={() => a.url && Linking.openURL(a.url)}
              >
                {a.name ?? 'Attachment'}
              </Text>
            ))}
          </View>
        )}

        {canCancel && (
          <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
            <Button
              title="Cancel this request"
              variant="danger"
              onPress={() => cancelMutation.mutate()}
              loading={cancelMutation.isPending}
            />
            {cancelMutation.isError && (
              <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>
                {extractErrorMessage(cancelMutation.error)}
              </Text>
            )}
          </View>
        )}
      </ScrollView>
    </Screen>
  );
}

function Row({
  label,
  value,
  theme,
  valueColor,
}: {
  label: string;
  value: string;
  theme: ReturnType<typeof useTheme>;
  valueColor?: string;
}) {
  return (
    <View style={styles.row}>
      <Text style={[typography.caption, { color: theme.textMuted }]}>{label}</Text>
      <Text style={[typography.body, { color: valueColor ?? theme.text, marginTop: 2 }]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  headerRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.md },
  dot: { width: 12, height: 12, borderRadius: 6 },
  card: {
    borderRadius: radii.xl,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  row: { marginBottom: spacing.sm + 2 },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: spacing.md },
});
