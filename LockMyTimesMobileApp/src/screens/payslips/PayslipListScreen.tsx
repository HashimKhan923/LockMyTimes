import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchPayslips } from '../../api/endpoints/payslips';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { PayslipInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'PayslipList'>;

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

type StatusFilter = 'paid' | 'finalized' | 'cancelled' | undefined;

const STATUS_CHIPS: { key: StatusFilter; label: string }[] = [
  { key: undefined, label: 'All' },
  { key: 'paid', label: 'Paid' },
  { key: 'finalized', label: 'Issued' },
  { key: 'cancelled', label: 'Cancelled' },
];

export function PayslipListScreen({ navigation }: Props) {
  const theme = useTheme();
  const [selectedYear, setSelectedYear] = useState<number | undefined>(undefined);
  const [selectedStatus, setSelectedStatus] = useState<StatusFilter>(undefined);

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['payslips', 'index', selectedYear, selectedStatus],
    queryFn: () => fetchPayslips(selectedYear, selectedStatus),
  });

  function chipCount(key: StatusFilter): number {
    const counters = data?.counters;
    if (!counters) return 0;
    if (key === undefined) return counters.total;
    return counters[key] ?? 0;
  }

  function renderItem({ item, index }: { item: PayslipInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('PayslipDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.pay_date}</Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>{item.payslip_number}</Text>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            <Text style={[typography.subheading, { color: theme.primary }]}>
              {currencyFormatter.format(item.net_pay)}
            </Text>
            <StatusBadge
              value={item.status}
              label={STATUS_LABEL[item.status] ?? item.status}
              color={theme[STATUS_COLOR[item.status] ?? 'textMuted']}
              filled
            />
          </View>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <Text style={[typography.title, { color: '#FFFFFF' }]}>Payslips</Text>
        {data?.ytd && (
          <StatNumber
            value={currencyFormatter.format(data.ytd.net)}
            label={`Year to date (${data.year}) · Gross ${currencyFormatter.format(data.ytd.gross)} · ${data.ytd.count} payslip(s)`}
            size="lg"
            color="#FFFFFF"
            labelColor="rgba(255,255,255,0.85)"
            style={{ marginTop: spacing.sm }}
          />
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

        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={STATUS_CHIPS}
          keyExtractor={(c) => c.key ?? 'all'}
          style={{ marginTop: spacing.sm }}
          contentContainerStyle={{ gap: spacing.sm }}
          renderItem={({ item: chip }) => {
            const active = selectedStatus === chip.key;
            return (
              <Pressable
                onPress={() => setSelectedStatus(chip.key)}
                style={[
                  styles.statusChip,
                  {
                    backgroundColor: active ? theme.primary : theme.surface,
                    borderColor: active ? theme.primary : theme.border,
                  },
                ]}
              >
                <Text style={{ color: active ? theme.onPrimary : theme.text, fontWeight: '700', fontSize: 13 }}>
                  {chip.label}
                </Text>
                <View
                  style={[
                    styles.chipCountBadge,
                    { backgroundColor: active ? 'rgba(255,255,255,0.25)' : theme.surfaceAlt },
                  ]}
                >
                  <Text style={{ color: active ? theme.onPrimary : theme.textMuted, fontWeight: '700', fontSize: 10 }}>
                    {chipCount(chip.key)}
                  </Text>
                </View>
              </Pressable>
            );
          }}
        />
      </View>

      <FlatList
        data={data?.payslips ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No payslips yet.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  yearChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm - 2, borderRadius: radii.pill, borderWidth: 1 },
  statusChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm - 2,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
  chipCountBadge: {
    borderRadius: radii.pill,
    paddingHorizontal: 6,
    paddingVertical: 1,
    minWidth: 18,
    alignItems: 'center',
  },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
