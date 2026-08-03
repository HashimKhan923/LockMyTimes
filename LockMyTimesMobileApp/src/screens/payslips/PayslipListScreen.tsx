import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchPayslips } from '../../api/endpoints/payslips';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { PayslipInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'PayslipList'>;

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

export function PayslipListScreen({ navigation }: Props) {
  const theme = useTheme();

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['payslips', 'index'],
    queryFn: () => fetchPayslips(),
  });

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
          <Text style={[typography.subheading, { color: theme.success }]}>
            {currencyFormatter.format(item.net_pay)}
          </Text>
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
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
