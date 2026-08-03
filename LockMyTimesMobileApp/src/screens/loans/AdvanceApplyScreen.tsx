import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { applyAdvance } from '../../api/endpoints/loans';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'AdvanceApply'>;

export function AdvanceApplyScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();

  const [amount, setAmount] = useState('');
  const [reason, setReason] = useState('');
  const [repaymentType, setRepaymentType] = useState<'one_time' | 'installments'>('one_time');
  const [installments, setInstallments] = useState('3');
  const [error, setError] = useState<string | null>(null);

  const submitMutation = useMutation({
    mutationFn: () =>
      applyAdvance({
        amount: parseFloat(amount),
        reason,
        repayment_type: repaymentType,
        installments_count: repaymentType === 'installments' ? parseInt(installments, 10) : undefined,
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
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Request an advance</Text>

        <TextField label="Amount" keyboardType="decimal-pad" value={amount} onChangeText={setAmount} />

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.sm }]}>Repayment</Text>
        <View style={styles.row}>
          {(['one_time', 'installments'] as const).map((t) => (
            <Pressable
              key={t}
              onPress={() => setRepaymentType(t)}
              style={[
                styles.chip,
                { backgroundColor: repaymentType === t ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
              ]}
            >
              <Text style={{ color: repaymentType === t ? theme.onPrimary : theme.text, fontWeight: '600' }}>
                {t === 'one_time' ? 'One-time' : 'Installments'}
              </Text>
            </Pressable>
          ))}
        </View>

        {repaymentType === 'installments' && (
          <TextField label="Number of installments (max 12)" keyboardType="number-pad" value={installments} onChangeText={setInstallments} />
        )}

        <TextField label="Reason" multiline numberOfLines={3} value={reason} onChangeText={setReason} />

        {error && <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{error}</Text>}

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button
            title="Submit request"
            onPress={() => submitMutation.mutate()}
            loading={submitMutation.isPending}
            disabled={!amount || !reason.trim()}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm, marginBottom: spacing.md },
  chip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
});
