import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { applyLoan, calculateEmi, fetchLoanTypes } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'LoanApply'>;

function toDateString(d: Date) {
  return d.toISOString().slice(0, 10);
}

export function LoanApplyScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();

  const { data } = useQuery({ queryKey: ['loans', 'types'], queryFn: fetchLoanTypes });

  const [typeId, setTypeId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
  const [tenure, setTenure] = useState('12');
  const [purpose, setPurpose] = useState('');
  const [firstEmiDate, setFirstEmiDate] = useState(new Date(Date.now() + 30 * 86400000));
  const [showPicker, setShowPicker] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selectedType = data?.loan_types.find((t) => t.id === typeId);
  const principal = parseFloat(amount) || 0;
  const tenureMonths = parseInt(tenure, 10) || 0;

  const emiQuery = useQuery({
    queryKey: ['loans', 'emi', principal, tenureMonths, selectedType?.default_interest_rate],
    queryFn: () => calculateEmi(principal, tenureMonths, selectedType?.default_interest_rate ?? 0),
    enabled: principal > 0 && tenureMonths > 0 && !!selectedType,
  });

  const submitMutation = useMutation({
    mutationFn: () =>
      applyLoan({
        loan_type_id: typeId!,
        principal_amount: principal,
        tenure_months: tenureMonths,
        purpose,
        first_emi_date: toDateString(firstEmiDate),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['loans'] });
      navigation.goBack();
    },
    onError: (err) => setError(extractErrorMessage(err)),
  });

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Apply for a loan</Text>

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Loan type</Text>
        <View style={styles.typeList}>
          {(data?.loan_types ?? []).map((t) => (
            <Pressable
              key={t.id}
              onPress={() => t.is_eligible && setTypeId(t.id)}
              disabled={!t.is_eligible}
              style={[
                styles.typeCard,
                {
                  backgroundColor: typeId === t.id ? t.color : theme.surface,
                  borderColor: theme.border,
                  opacity: t.is_eligible ? 1 : 0.5,
                },
              ]}
            >
              <Text style={{ color: typeId === t.id ? '#fff' : theme.text, fontWeight: '700' }}>{t.name}</Text>
              <Text style={{ color: typeId === t.id ? '#fff' : theme.textMuted, fontSize: 12 }}>
                {t.default_interest_rate}% · up to {t.effective_max ?? t.max_amount}
              </Text>
              {!t.is_eligible && (t.block_reasons ?? []).map((r, i) => (
                <Text key={i} style={{ color: typeId === t.id ? '#fff' : theme.danger, fontSize: 11 }}>
                  {r}
                </Text>
              ))}
            </Pressable>
          ))}
        </View>

        <TextField label="Amount" keyboardType="decimal-pad" value={amount} onChangeText={setAmount} />
        <TextField label="Tenure (months)" keyboardType="number-pad" value={tenure} onChangeText={setTenure} />

        <Pressable
          onPress={() => setShowPicker(true)}
          style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
        >
          <Text style={[typography.caption, { color: theme.textMuted }]}>First EMI date</Text>
          <Text style={[typography.body, { color: theme.text }]}>{toDateString(firstEmiDate)}</Text>
        </Pressable>
        {showPicker && (
          <DateTimePicker
            value={firstEmiDate}
            mode="date"
            minimumDate={new Date()}
            display={Platform.OS === 'ios' ? 'inline' : 'default'}
            onChange={(_, selected) => {
              setShowPicker(Platform.OS === 'ios');
              if (selected) setFirstEmiDate(selected);
            }}
          />
        )}

        <TextField label="Purpose" multiline numberOfLines={3} value={purpose} onChangeText={setPurpose} />

        {emiQuery.data && (
          <MotiView from={{ opacity: 0 }} animate={{ opacity: 1 }} style={[styles.emiCard, { backgroundColor: theme.primaryMuted }]}>
            <Text style={[typography.subheading, { color: theme.text }]}>
              Monthly EMI: ${emiQuery.data.emi.toFixed(2)}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              Total repayable: ${emiQuery.data.total_amount.toFixed(2)} (interest ${emiQuery.data.total_interest.toFixed(2)})
            </Text>
          </MotiView>
        )}

        {error && <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{error}</Text>}

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button
            title="Submit application"
            onPress={() => submitMutation.mutate()}
            loading={submitMutation.isPending}
            disabled={!typeId || !principal || !tenureMonths || !purpose.trim()}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  typeList: { gap: spacing.sm, marginTop: spacing.sm, marginBottom: spacing.md },
  typeCard: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md },
  dateBox: { borderWidth: 1, borderRadius: radii.md, padding: spacing.sm + 4, marginBottom: spacing.md },
  emiCard: { borderRadius: radii.md, padding: spacing.md, marginTop: spacing.md },
});
