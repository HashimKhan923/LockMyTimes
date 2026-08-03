import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import DateTimePicker from '@react-native-community/datetimepicker';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { createExpense, fetchExpenses } from '../../api/endpoints/expenses';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { ToggleRow } from '../../components/common/ToggleRow';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseCreate'>;

const hintFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

function toDateString(d: Date) {
  return d.toISOString().slice(0, 10);
}

export function ExpenseCreateScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();

  const { data } = useQuery({ queryKey: ['expenses', 'index'], queryFn: () => fetchExpenses() });

  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [amount, setAmount] = useState('');
  const [merchant, setMerchant] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [projectCode, setProjectCode] = useState('');
  const [isMileage, setIsMileage] = useState(false);
  const [miles, setMiles] = useState('');
  const [mileageRate, setMileageRate] = useState('');
  const [date, setDate] = useState(new Date());
  const [showPicker, setShowPicker] = useState(false);
  const [receipt, setReceipt] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [error, setError] = useState<string | null>(null);

  const selectedCategory = data?.categories.find((c) => c.id === categoryId);
  const amountNum = parseFloat(amount) || 0;
  const limit = selectedCategory?.per_expense_limit ?? selectedCategory?.max_amount ?? null;
  const overLimit = limit != null && amountNum > 0 && amountNum > limit;
  const receiptThreshold = selectedCategory?.receipt_required_above ?? 0;
  const needsReceiptWarning =
    !!selectedCategory?.requires_receipt && !receipt && amountNum > 0 && amountNum > receiptThreshold;

  const submitMutation = useMutation({
    mutationFn: () =>
      createExpense({
        category_id: categoryId!,
        title,
        amount: amountNum,
        expense_date: toDateString(date),
        merchant: merchant || undefined,
        payment_method: paymentMethod || undefined,
        project_code: projectCode || undefined,
        is_mileage: isMileage || undefined,
        miles: isMileage && miles ? parseFloat(miles) : undefined,
        mileage_rate: isMileage && mileageRate ? parseFloat(mileageRate) : undefined,
        receipt: receipt
          ? { uri: receipt.uri, name: receipt.fileName ?? 'receipt.jpg', mimeType: receipt.mimeType ?? 'image/jpeg' }
          : undefined,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] });
      navigation.goBack();
    },
    onError: (err) => setError(extractErrorMessage(err)),
  });

  async function pickReceipt() {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) return;
    const result = await ImagePicker.launchCameraAsync({ quality: 0.6 });
    if (!result.canceled && result.assets?.[0]) setReceipt(result.assets[0]);
  }

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Add expense</Text>

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Category</Text>
        <View style={styles.chipRow}>
          {(data?.categories ?? []).map((c) => (
            <Pressable
              key={c.id}
              onPress={() => setCategoryId(c.id)}
              style={[
                styles.chip,
                { backgroundColor: categoryId === c.id ? c.color : theme.surface, borderColor: theme.border },
              ]}
            >
              <Text style={{ color: categoryId === c.id ? '#fff' : theme.text, fontWeight: '600' }}>{c.name}</Text>
            </Pressable>
          ))}
        </View>

        {selectedCategory && (
          <View style={[styles.hintBox, { backgroundColor: theme.surfaceAlt, borderColor: theme.border }]}>
            {selectedCategory.per_expense_limit != null && (
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                Per-expense limit: {hintFormatter.format(selectedCategory.per_expense_limit)}
              </Text>
            )}
            {selectedCategory.max_amount != null && (
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                Category max: {hintFormatter.format(selectedCategory.max_amount)}
              </Text>
            )}
            {selectedCategory.requires_receipt && (
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                Receipt required
                {selectedCategory.receipt_required_above != null
                  ? ` above ${hintFormatter.format(selectedCategory.receipt_required_above)}`
                  : ''}
              </Text>
            )}
          </View>
        )}

        <TextField label="Title" value={title} onChangeText={setTitle} />
        <TextField label="Amount" keyboardType="decimal-pad" value={amount} onChangeText={setAmount} />
        {overLimit && (
          <Text style={[typography.caption, { color: theme.danger, marginTop: -spacing.sm, marginBottom: spacing.md }]}>
            Amount exceeds this category's limit of {hintFormatter.format(limit!)}.
          </Text>
        )}
        {!overLimit && needsReceiptWarning && (
          <Text style={[typography.caption, { color: theme.warning, marginTop: -spacing.sm, marginBottom: spacing.md }]}>
            A receipt will be required for this amount.
          </Text>
        )}
        <TextField label="Merchant (optional)" value={merchant} onChangeText={setMerchant} />
        <TextField label="Payment method (optional)" value={paymentMethod} onChangeText={setPaymentMethod} />
        <TextField label="Project code (optional)" value={projectCode} onChangeText={setProjectCode} />

        <ToggleRow label="Mileage expense" value={isMileage} onValueChange={setIsMileage} />
        {isMileage && (
          <View style={styles.mileageRow}>
            <View style={{ flex: 1 }}>
              <TextField label="Miles" keyboardType="decimal-pad" value={miles} onChangeText={setMiles} />
            </View>
            <View style={{ flex: 1 }}>
              <TextField label="Rate per mile" keyboardType="decimal-pad" value={mileageRate} onChangeText={setMileageRate} />
            </View>
          </View>
        )}

        <Pressable
          onPress={() => setShowPicker(true)}
          style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
        >
          <Text style={[typography.caption, { color: theme.textMuted }]}>Expense date</Text>
          <Text style={[typography.body, { color: theme.text }]}>{toDateString(date)}</Text>
        </Pressable>
        {showPicker && (
          <DateTimePicker
            value={date}
            mode="date"
            maximumDate={new Date()}
            display={Platform.OS === 'ios' ? 'inline' : 'default'}
            onChange={(_, selected) => {
              setShowPicker(Platform.OS === 'ios');
              if (selected) setDate(selected);
            }}
          />
        )}

        <Pressable
          onPress={pickReceipt}
          style={[styles.receiptBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
        >
          <Text style={{ color: receipt ? theme.text : theme.textMuted }}>
            {receipt ? 'Receipt captured ✓' : `Capture receipt${selectedCategory?.requires_receipt ? ' (required)' : ''}`}
          </Text>
        </Pressable>

        {error && <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{error}</Text>}

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button
            title="Submit for approval"
            onPress={() => submitMutation.mutate()}
            loading={submitMutation.isPending}
            disabled={!categoryId || !title.trim() || !amount}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.sm, marginBottom: spacing.md },
  chip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  hintBox: {
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.sm + 4,
    marginBottom: spacing.md,
    gap: 2,
  },
  mileageRow: { flexDirection: 'row', gap: spacing.sm },
  dateBox: { borderWidth: 1, borderRadius: radii.md, padding: spacing.sm + 4, marginBottom: spacing.md },
  receiptBox: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md, alignItems: 'center', marginBottom: spacing.md },
});
