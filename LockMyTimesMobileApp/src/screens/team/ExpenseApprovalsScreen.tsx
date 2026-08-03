import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { FlatList, Modal, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import { approveExpense, fetchExpenseApprovals, rejectExpense } from '../../api/endpoints/team';
import { AvatarStack } from '../../components/common/AvatarStack';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { TextField } from '../../components/common/TextField';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { ExpenseInfo } from '../../api/types';

const currencyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' });

function formatCurrency(amount: number, currency?: string | null) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(amount);
}

export function ExpenseApprovalsScreen() {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [rejectingId, setRejectingId] = useState<number | null>(null);
  const [reason, setReason] = useState('');

  const { data } = useQuery({ queryKey: ['team', 'expense-approvals'], queryFn: () => fetchExpenseApprovals() });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['team'] });

  const approveMutation = useMutation({ mutationFn: (id: number) => approveExpense(id), onSuccess: invalidate });

  const rejectMutation = useMutation({
    mutationFn: () => rejectExpense(rejectingId!, reason),
    onSuccess: () => {
      setRejectingId(null);
      setReason('');
      invalidate();
    },
  });

  function renderItem({ item, index }: { item: ExpenseInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          {item.employee && (
            <View style={styles.requesterRow}>
              <AvatarStack
                people={[{ id: item.employee.id, name: item.employee.full_name, avatar_url: item.employee.avatar_url }]}
                max={1}
                size={28}
              />
              <View style={{ marginLeft: spacing.sm }}>
                <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>{item.employee.full_name}</Text>
                {item.employee.position && (
                  <Text style={[typography.caption, { color: theme.textMuted }]}>{item.employee.position}</Text>
                )}
              </View>
            </View>
          )}

          <View style={styles.headerRow}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.title}</Text>
            <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>
              {formatCurrency(item.amount, item.currency)}
            </Text>
          </View>
          <View style={styles.categoryRow}>
            <StatusBadge value={item.category.name} color={item.category.color} uppercase={false} filled />
            <Text style={[typography.caption, { color: theme.textMuted }]}>{item.expense_date}</Text>
          </View>

          <View style={styles.actionRow}>
            <Button title="Approve" onPress={() => approveMutation.mutate(item.id)} loading={approveMutation.isPending} />
            <View style={{ width: spacing.sm }} />
            <Button title="Reject" variant="danger" onPress={() => setRejectingId(item.id)} />
          </View>
        </View>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Expense approvals</Text>
        {data && (
          <StatNumber
            value={currencyFormatter.format(data.pending_total)}
            label="Pending total"
            color={theme.warning}
            style={{ marginTop: spacing.xs }}
          />
        )}
      </View>

      <FlatList
        data={data?.expenses ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={styles.padded}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No pending expenses.
          </Text>
        }
      />

      <Modal visible={rejectingId !== null} transparent animationType="fade" onRequestClose={() => setRejectingId(null)}>
        <Pressable style={styles.backdrop} onPress={() => setRejectingId(null)}>
          <Pressable style={[styles.sheet, { backgroundColor: theme.surface }]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Reject reason</Text>
            <TextField label="" placeholder="Explain why you're rejecting this" multiline numberOfLines={3} value={reason} onChangeText={setReason} />
            <Button title="Confirm rejection" variant="danger" onPress={() => rejectMutation.mutate()} loading={rejectMutation.isPending} disabled={reason.trim().length < 5} />
          </Pressable>
        </Pressable>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  requesterRow: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.sm },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between' },
  categoryRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: 4 },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
  actionRow: { flexDirection: 'row', marginTop: spacing.md },
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { borderTopLeftRadius: radii.lg, borderTopRightRadius: radii.lg, padding: spacing.lg },
});
