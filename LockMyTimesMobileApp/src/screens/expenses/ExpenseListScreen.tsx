import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchExpenses } from '../../api/endpoints/expenses';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { ExpenseInfo, ExpensesIndexResponse } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseList'>;

/** Aggregate-level totals (year summary, category breakdown) have no single currency of their own — kept as a generic formatter. Per-expense amounts use `formatCurrency` with that row's own `currency`. */
const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

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

type CounterKey = keyof ExpensesIndexResponse['counters'];

const STATUS_FILTERS: { key: CounterKey; label: string }[] = [
  { key: 'total', label: 'All' },
  { key: 'draft', label: 'Draft' },
  { key: 'submitted', label: 'Submitted' },
  { key: 'approved', label: 'Approved' },
  { key: 'rejected', label: 'Rejected' },
  { key: 'paid', label: 'Paid' },
];

export function ExpenseListScreen({ navigation }: Props) {
  const theme = useTheme();
  const [selectedYear, setSelectedYear] = useState<number | undefined>(undefined);
  const [selectedStatus, setSelectedStatus] = useState<CounterKey>('total');

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['expenses', 'index', selectedYear, selectedStatus],
    queryFn: () => fetchExpenses(selectedYear, selectedStatus === 'total' ? undefined : selectedStatus),
  });

  function renderItem({ item, index }: { item: ExpenseInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('ExpenseDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.title}</Text>
            <View style={styles.categoryRow}>
              <StatusBadge value={item.category.name} color={item.category.color} uppercase={false} filled />
              <Text style={[typography.caption, { color: theme.textMuted }]}>{item.expense_date}</Text>
            </View>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {formatCurrency(item.amount, item.currency)}
            </Text>
            <StatusBadge value={item.status} color={theme[STATUS_COLOR[item.status]]} filled />
          </View>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroRow}>
          <View>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Expenses</Text>
            {data?.totals && (
              <StatNumber
                value={currencyFormatter.format(data.totals.reimbursable_amount)}
                label="Reimbursable"
                size="lg"
                color="#FFFFFF"
                labelColor="rgba(255,255,255,0.85)"
                style={{ marginTop: spacing.sm }}
              />
            )}
          </View>
          <Button title="Add" variant="accent" icon="add" compact onPress={() => navigation.navigate('ExpenseCreate')} />
        </View>
      </HeroHeader>

      <View style={styles.controlsWrap}>
        {(data?.years.length ?? 0) > 1 && (
          <FlatList
            horizontal
            showsHorizontalScrollIndicator={false}
            data={data!.years}
            keyExtractor={(y) => String(y)}
            contentContainerStyle={styles.chipListContent}
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

        {data?.counters && (
          <FlatList
            horizontal
            showsHorizontalScrollIndicator={false}
            data={STATUS_FILTERS}
            keyExtractor={(f) => f.key}
            style={{ marginTop: spacing.sm }}
            contentContainerStyle={styles.chipListContent}
            renderItem={({ item: f }) => {
              const active = selectedStatus === f.key;
              const activeColor = f.key === 'total' ? theme.primary : theme[STATUS_COLOR[f.key] ?? 'textMuted'];
              const count = data.counters[f.key];
              return (
                <Pressable
                  onPress={() => setSelectedStatus(f.key)}
                  style={[
                    styles.statusChip,
                    {
                      backgroundColor: active ? activeColor : theme.surface,
                      borderColor: active ? activeColor : theme.border,
                    },
                  ]}
                >
                  <Text
                    style={{
                      color: active ? theme.onPrimary : theme.text,
                      fontWeight: '600',
                      fontSize: typography.caption.fontSize,
                    }}
                  >
                    {f.label} · {count}
                  </Text>
                </Pressable>
              );
            }}
          />
        )}

        {(data?.by_category.length ?? 0) > 0 && (
          <View style={{ marginTop: spacing.md }}>
            <Text style={[typography.caption, { color: theme.textMuted, marginBottom: spacing.xs }]}>Top categories</Text>
            <FlatList
              horizontal
              showsHorizontalScrollIndicator={false}
              data={data!.by_category}
              keyExtractor={(c) => String(c.category.id)}
              contentContainerStyle={styles.chipListContent}
              renderItem={({ item: c }) => (
                <View
                  style={[
                    styles.categoryTile,
                    { backgroundColor: theme.surface },
                    Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
                  ]}
                >
                  <View style={styles.categoryTileHeader}>
                    <View style={[styles.categoryDot, { backgroundColor: c.category.color }]} />
                    <Text numberOfLines={1} style={[typography.caption, { color: theme.textMuted }]}>
                      {c.category.name}
                    </Text>
                  </View>
                  <Text style={[typography.subheading, { color: theme.text, marginTop: 2 }]}>
                    {currencyFormatter.format(c.total)}
                  </Text>
                  <Text style={[typography.caption, { color: theme.textMuted }]}>
                    {c.count} item{c.count === 1 ? '' : 's'}
                  </Text>
                </View>
              )}
            />
          </View>
        )}
      </View>

      <FlatList
        data={data?.expenses ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No expenses yet.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.sm },
  controlsWrap: { paddingHorizontal: 24, paddingTop: spacing.md },
  chipListContent: { gap: spacing.sm },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  categoryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: 4 },
  yearChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm - 2, borderRadius: radii.pill, borderWidth: 1 },
  statusChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm - 2, borderRadius: radii.pill, borderWidth: 1 },
  categoryTile: { borderRadius: radii.md, padding: spacing.sm, minWidth: 120 },
  categoryTileHeader: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  categoryDot: { width: 8, height: 8, borderRadius: 4 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
