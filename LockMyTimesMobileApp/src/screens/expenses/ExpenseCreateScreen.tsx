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
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'ExpenseCreate'>;

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
  const [date, setDate] = useState(new Date());
  const [showPicker, setShowPicker] = useState(false);
  const [receipt, setReceipt] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [error, setError] = useState<string | null>(null);

  const selectedCategory = data?.categories.find((c) => c.id === categoryId);

  const submitMutation = useMutation({
    mutationFn: () =>
      createExpense({
        category_id: categoryId!,
        title,
        amount: parseFloat(amount),
        expense_date: toDateString(date),
        merchant: merchant || undefined,
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

        <TextField label="Title" value={title} onChangeText={setTitle} />
        <TextField label="Amount" keyboardType="decimal-pad" value={amount} onChangeText={setAmount} />
        <TextField label="Merchant (optional)" value={merchant} onChangeText={setMerchant} />

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
  dateBox: { borderWidth: 1, borderRadius: radii.md, padding: spacing.sm + 4, marginBottom: spacing.md },
  receiptBox: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md, alignItems: 'center', marginBottom: spacing.md },
});
