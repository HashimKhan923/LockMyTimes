import { useQuery } from '@tanstack/react-query';
import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { useState } from 'react';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { API_BASE_URL } from '../../api/client';
import { fetchPayslip, payslipPdfUrl } from '../../api/endpoints/payslips';
import { Button } from '../../components/common/Button';
import { GradientCard } from '../../components/common/GradientCard';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { useAuthStore } from '../../stores/authStore';
import { springTransition } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'PayslipDetail'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

export function PayslipDetailScreen({ route }: Props) {
  const theme = useTheme();
  const { id } = route.params;
  const { token, tenantSlug } = useAuthStore();
  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['payslips', 'detail', id],
    queryFn: () => fetchPayslip(id),
  });

  async function handleDownload() {
    setDownloading(true);
    setDownloadError(null);
    try {
      const destination = new File(Paths.cache, `payslip-${id}.pdf`);
      const task = File.createDownloadTask(`${API_BASE_URL}${payslipPdfUrl(id)}`, destination, {
        headers: {
          Authorization: `Bearer ${token}`,
          'X-Tenant': tenantSlug ?? '',
        },
      });
      const file = await task.downloadAsync();
      if (file && (await Sharing.isAvailableAsync())) {
        await Sharing.shareAsync(file.uri);
      }
    } catch {
      setDownloadError('Could not download the payslip. Please try again.');
    } finally {
      setDownloading(false);
    }
  }

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const ps = data.payslip;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{ps.payslip_number}</Text>
        <Text style={[typography.caption, { color: theme.textMuted }]}>
          {ps.period_start} – {ps.period_end} · Paid {ps.pay_date}
        </Text>

        <MotiView from={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} transition={springTransition}>
          <GradientCard colors={theme.gradients.accent} style={styles.heroCard}>
            <StatNumber
              value={currencyFormatter.format(ps.net_pay)}
              label="Net pay"
              size="lg"
              color={theme.onPrimary}
              labelColor="rgba(255,255,255,0.85)"
            />
          </GradientCard>
        </MotiView>

        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          {ps.items.map((item) => (
            <View key={item.id} style={styles.itemRow}>
              <View style={styles.itemLabelRow}>
                <Text style={[typography.body, { color: theme.text }]}>{item.label}</Text>
                <StatusBadge
                  value={item.type}
                  label={item.type === 'deduction' ? 'Deduction' : 'Earning'}
                  color={item.type === 'deduction' ? theme.danger : theme.success}
                  filled
                />
              </View>
              <Text
                style={[
                  typography.body,
                  { color: item.type === 'deduction' ? theme.danger : theme.success, fontWeight: '600' },
                ]}
              >
                {item.type === 'deduction' ? '-' : '+'}
                {currencyFormatter.format(item.amount)}
              </Text>
            </View>
          ))}
          <View style={[styles.itemRow, { borderTopWidth: StyleSheet.hairlineWidth, borderColor: theme.border, marginTop: spacing.xs, paddingTop: spacing.sm }]}>
            <Text style={[typography.subheading, { color: theme.text }]}>Gross pay</Text>
            <Text style={[typography.subheading, { color: theme.text }]}>{currencyFormatter.format(ps.gross_pay)}</Text>
          </View>
        </View>

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button title="Download / Share PDF" onPress={handleDownload} loading={downloading} />
          {downloadError && (
            <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{downloadError}</Text>
          )}
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  heroCard: { marginTop: spacing.lg },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  itemLabelRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
});
