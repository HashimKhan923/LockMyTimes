import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { MotiView } from 'moti';
import { cancelAdvance, fetchAdvance } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { GradientCard } from '../../components/common/GradientCard';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { springTransition } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'AdvanceDetail'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const ADVANCE_STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  active: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
  closed: 'textMuted',
};

export function AdvanceDetailScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;

  const { data, isLoading } = useQuery({ queryKey: ['loans', 'advance-detail', id], queryFn: () => fetchAdvance(id) });

  const cancelMutation = useMutation({
    mutationFn: () => cancelAdvance(id),
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

  const adv = data.advance;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>{adv.advance_number}</Text>

        <MotiView from={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} transition={springTransition}>
          <GradientCard colors={theme.gradients.accent} style={styles.heroCard}>
            <View style={styles.heroRow}>
              <ProgressRing
                percent={adv.progress_pct}
                size={80}
                strokeWidth={8}
                gradientColors={['#FFFFFF', 'rgba(255,255,255,0.55)']}
                trackColor="rgba(255,255,255,0.25)"
              >
                <Text style={{ color: theme.onPrimary, fontWeight: '800' }}>{adv.progress_pct}%</Text>
              </ProgressRing>
              <View style={{ marginLeft: spacing.lg, flex: 1 }}>
                <Text style={[typography.title, { color: theme.onPrimary }]}>{currencyFormatter.format(adv.amount)}</Text>
                <View style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}>
                  <StatusBadge value={adv.status} color={theme.onPrimary} filled />
                </View>
              </View>
            </View>
          </GradientCard>
        </MotiView>

        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <Row label="Reason" value={adv.reason} theme={theme} />
          <Row label="Repayment" value={adv.repayment_type === 'installments' ? `${adv.installments_count} installments` : 'One-time'} theme={theme} />
          <Row label="Remaining" value={currencyFormatter.format(adv.amount_remaining)} theme={theme} />
          <View style={styles.row}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>Status</Text>
            <StatusBadge value={adv.status} color={theme[ADVANCE_STATUS_COLOR[adv.status] ?? 'textMuted']} filled />
          </View>
          {adv.rejection_reason && <Row label="Rejection reason" value={adv.rejection_reason} theme={theme} valueColor={theme.danger} />}
        </View>

        {adv.status === 'pending' && (
          <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
            <Button title="Cancel request" variant="danger" onPress={() => cancelMutation.mutate()} loading={cancelMutation.isPending} />
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
  heroCard: { marginTop: spacing.md },
  heroRow: { flexDirection: 'row', alignItems: 'center' },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  row: { marginBottom: spacing.sm },
});
