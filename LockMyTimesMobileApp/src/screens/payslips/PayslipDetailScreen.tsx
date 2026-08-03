import { useQuery } from '@tanstack/react-query';
import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { API_BASE_URL } from '../../api/client';
import { fetchPayslip, payslipPdfUrl } from '../../api/endpoints/payslips';
import { Button } from '../../components/common/Button';
import { GradientCard } from '../../components/common/GradientCard';
import { Icon } from '../../components/common/Icon';
import { IconInfoCard, IconInfoRow } from '../../components/common/IconInfoRow';
import { Screen } from '../../components/common/Screen';
import { StatCircleTile } from '../../components/common/StatCircleTile';
import { StatNumber, StatTile } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { useAuthStore } from '../../stores/authStore';
import { springTransition } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { PayslipItemInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'PayslipDetail'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  paid: 'success',
  finalized: 'warning',
  cancelled: 'danger',
  draft: 'textMuted',
};

const STATUS_LABEL: Record<string, string> = {
  paid: 'Paid',
  finalized: 'Issued',
  cancelled: 'Cancelled',
  draft: 'Draft',
};

function titleCase(value: string | null | undefined): string {
  if (!value) return '—';
  return value
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

function itemTypeLabel(type: string): string {
  return type.charAt(0).toUpperCase() + type.slice(1);
}

export function PayslipDetailScreen({ route, navigation }: Props) {
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

  const earningItems = ps.items.filter((i: PayslipItemInfo) => i.type === 'earning' || i.type === 'reimbursement');
  const deductionItems = ps.items.filter((i: PayslipItemInfo) => i.type === 'deduction' || i.type === 'tax');

  const initials = ps.employee?.full_name
    ? ps.employee.full_name
        .split(' ')
        .map((p) => p[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase()
    : '—';

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        {/* Top nav row: title + prev/next */}
        <View style={styles.topRow}>
          <View style={{ flex: 1 }}>
            <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{ps.payslip_number}</Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {ps.period_start} – {ps.period_end} · Paid {ps.pay_date}
            </Text>
          </View>
          <View style={styles.navArrows}>
            <Pressable
              disabled={!ps.prev}
              onPress={() => ps.prev && navigation.setParams({ id: ps.prev.id })}
              style={[
                styles.navBtn,
                { backgroundColor: theme.surface, opacity: ps.prev ? 1 : 0.35 },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              <Icon name="arrow-back" size={16} color={theme.text} />
            </Pressable>
            <Pressable
              disabled={!ps.next}
              onPress={() => ps.next && navigation.setParams({ id: ps.next.id })}
              style={[
                styles.navBtn,
                { backgroundColor: theme.surface, opacity: ps.next ? 1 : 0.35 },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              <Icon name="chevron-forward" size={16} color={theme.text} />
            </Pressable>
          </View>
        </View>

        <View style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}>
          <StatusBadge
            value={ps.status}
            label={STATUS_LABEL[ps.status] ?? titleCase(ps.status)}
            color={theme[STATUS_COLOR[ps.status] ?? 'textMuted']}
            filled
          />
        </View>

        {/* Employee identity */}
        {ps.employee && (
          <View
            style={[
              styles.card,
              { backgroundColor: theme.surface },
              Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
            ]}
          >
            <View style={styles.identityRow}>
              <View style={[styles.avatarBox, { backgroundColor: theme.primaryMuted }]}>
                <Text style={[styles.avatarInitials, { color: theme.primary }]}>{initials}</Text>
              </View>
              <View style={{ flex: 1, marginLeft: spacing.md }}>
                <Text style={[typography.subheading, { color: theme.text }]}>{ps.employee.full_name}</Text>
                <Text style={[typography.caption, { color: theme.textMuted }]}>{ps.employee.employee_code ?? '—'}</Text>
              </View>
            </View>
            {(ps.employee.position || ps.employee.department) && (
              <View style={{ marginTop: spacing.sm }}>
                {ps.employee.position && (
                  <Text style={[typography.caption, { color: theme.textMuted }]}>{ps.employee.position}</Text>
                )}
                {ps.employee.department && (
                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
                    {ps.employee.department}
                  </Text>
                )}
              </View>
            )}
          </View>
        )}

        {/* Pay period / schedule / payment method */}
        <View style={{ marginTop: spacing.md }}>
          <IconInfoCard>
            <IconInfoRow icon="calendar" label="Pay period" value={`${ps.period_start} – ${ps.period_end}`} />
            <IconInfoRow icon="calendar-outline" label="Pay date" value={ps.pay_date} />
            <IconInfoRow icon="time" label="Schedule" value={titleCase(ps.pay_schedule)} />
            <IconInfoRow icon="cash-outline" label="Payment method" value={titleCase(ps.payment_method)} last />
          </IconInfoCard>
        </View>

        {/* Earnings */}
        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <Text style={[typography.caption, styles.sectionLabel, { color: theme.textMuted }]}>Earnings</Text>
          {ps.earnings.length === 0 && earningItems.length === 0 && (
            <Text style={[typography.caption, { color: theme.textMuted, fontStyle: 'italic' }]}>
              No earnings on this payslip.
            </Text>
          )}
          {ps.earnings.map((row, i) => (
            <View key={`e-${i}`} style={styles.itemRow}>
              <Text style={[typography.body, { color: theme.text }]}>{row.label}</Text>
              <Text style={[typography.body, { color: theme.primary, fontWeight: '600' }]}>
                {currencyFormatter.format(row.amount)}
              </Text>
            </View>
          ))}
          {earningItems.map((item) => (
            <View key={item.id} style={styles.itemRow}>
              <View style={styles.itemLabelRow}>
                <Text style={[typography.body, { color: theme.text }]}>{item.label}</Text>
                <StatusBadge value={item.type} label={itemTypeLabel(item.type)} color={theme.primary} filled />
              </View>
              <Text style={[typography.body, { color: theme.primary, fontWeight: '600' }]}>
                {currencyFormatter.format(item.amount)}
              </Text>
            </View>
          ))}
          <View
            style={[
              styles.itemRow,
              { borderTopWidth: StyleSheet.hairlineWidth, borderColor: theme.border, marginTop: spacing.xs, paddingTop: spacing.sm },
            ]}
          >
            <Text style={[typography.subheading, { color: theme.text }]}>Gross pay</Text>
            <Text style={[typography.subheading, { color: theme.text }]}>{currencyFormatter.format(ps.gross_pay)}</Text>
          </View>
        </View>

        {/* Deductions */}
        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <Text style={[typography.caption, styles.sectionLabel, { color: theme.textMuted }]}>Deductions</Text>
          {ps.deduction_breakdown.length === 0 && deductionItems.length === 0 && (
            <Text style={[typography.caption, { color: theme.textMuted, fontStyle: 'italic' }]}>
              No deductions on this payslip.
            </Text>
          )}
          {ps.deduction_breakdown.map((row, i) => (
            <View key={`d-${i}`} style={styles.itemRow}>
              <Text style={[typography.body, { color: theme.text }]}>{row.label}</Text>
              <Text style={[typography.body, { color: theme.categorical[1], fontWeight: '600' }]}>
                −{currencyFormatter.format(row.amount)}
              </Text>
            </View>
          ))}
          {deductionItems.map((item) => (
            <View key={item.id} style={styles.itemRow}>
              <View style={styles.itemLabelRow}>
                <Text style={[typography.body, { color: theme.text }]}>{item.label}</Text>
                <StatusBadge value={item.type} label={itemTypeLabel(item.type)} color={theme.categorical[1]} filled />
              </View>
              <Text style={[typography.body, { color: theme.categorical[1], fontWeight: '600' }]}>
                −{currencyFormatter.format(item.amount)}
              </Text>
            </View>
          ))}
          <View
            style={[
              styles.itemRow,
              { borderTopWidth: StyleSheet.hairlineWidth, borderColor: theme.border, marginTop: spacing.xs, paddingTop: spacing.sm },
            ]}
          >
            <Text style={[typography.subheading, { color: theme.text }]}>Total deductions</Text>
            <Text style={[typography.subheading, { color: theme.categorical[1] }]}>
              −{currencyFormatter.format(ps.total_deductions)}
            </Text>
          </View>
        </View>

        {/* Net pay hero */}
        <MotiView from={{ opacity: 0, scale: 0.97 }} animate={{ opacity: 1, scale: 1 }} transition={springTransition}>
          <GradientCard colors={theme.gradients.accent} style={styles.heroCard}>
            <StatNumber
              value={currencyFormatter.format(ps.net_pay)}
              label="Net pay · take-home amount"
              size="lg"
              color={theme.onPrimary}
              labelColor="rgba(255,255,255,0.85)"
            />
          </GradientCard>
        </MotiView>

        {/* YTD row */}
        <Text style={[typography.caption, styles.sectionLabel, { color: theme.textMuted, marginTop: spacing.lg }]}>
          Year to date
        </Text>
        <View style={styles.tileRow}>
          <StatTile value={currencyFormatter.format(ps.ytd_gross)} label="YTD Gross" color={theme.categorical[0]} />
          <StatTile value={currencyFormatter.format(ps.ytd_taxes)} label="YTD Taxes" color={theme.categorical[1]} />
          <StatTile value={currencyFormatter.format(ps.ytd_net)} label="YTD Net" color={theme.primary} />
        </View>

        {/* Hours row */}
        <Text style={[typography.caption, styles.sectionLabel, { color: theme.textMuted, marginTop: spacing.lg }]}>
          Hours
        </Text>
        <View style={styles.tileRow}>
          <StatCircleTile icon="time" value={ps.regular_hours.toFixed(1)} label="Regular hrs" color={theme.categorical[0]} />
          <StatCircleTile icon="lightning" value={ps.overtime_hours.toFixed(1)} label="Overtime hrs" color={theme.categorical[1]} />
        </View>
        <View style={[styles.tileRow, { marginTop: spacing.sm }]}>
          <StatCircleTile icon="calendar" value={ps.holiday_hours.toFixed(1)} label="Holiday hrs" color={theme.categorical[2]} />
          <StatCircleTile icon="calendar-outline" value={ps.leave_hours.toFixed(1)} label="Leave hrs" color={theme.categorical[3]} />
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
  topRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between' },
  navArrows: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  navBtn: { width: 36, height: 36, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
  heroCard: { marginTop: spacing.lg },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  sectionLabel: { textTransform: 'uppercase', letterSpacing: 0.5, fontWeight: '700', marginBottom: spacing.sm },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  itemLabelRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  identityRow: { flexDirection: 'row', alignItems: 'center' },
  avatarBox: { width: 48, height: 48, borderRadius: radii.md, alignItems: 'center', justifyContent: 'center' },
  avatarInitials: { fontSize: 18, fontWeight: '700' },
  tileRow: { flexDirection: 'row', gap: spacing.sm },
});
