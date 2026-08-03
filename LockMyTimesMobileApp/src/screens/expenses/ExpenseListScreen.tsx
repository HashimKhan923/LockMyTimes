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
import type { ExpenseInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseList'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  paid: 'success',
  submitted: 'warning',
  rejected: 'danger',
  draft: 'textMuted',
  cancelled: 'textMuted',
};

export function ExpenseListScreen({ navigation }: Props) {
  const theme = useTheme();

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['expenses', 'index'],
    queryFn: () => fetchExpenses(),
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
              {currencyFormatter.format(item.amount)}
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
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  categoryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: 4 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
