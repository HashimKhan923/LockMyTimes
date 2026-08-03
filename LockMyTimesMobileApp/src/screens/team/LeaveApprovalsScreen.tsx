import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { FlatList, Modal, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import { approveLeave, fetchLeaveApprovals, rejectLeave } from '../../api/endpoints/team';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { TextField } from '../../components/common/TextField';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { LeaveRequestInfo } from '../../api/types';

export function LeaveApprovalsScreen() {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [rejectingId, setRejectingId] = useState<number | null>(null);
  const [reason, setReason] = useState('');

  const { data } = useQuery({ queryKey: ['team', 'leave-approvals'], queryFn: () => fetchLeaveApprovals() });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['team'] });
  };

  const approveMutation = useMutation({
    mutationFn: (id: number) => approveLeave(id),
    onSuccess: invalidate,
  });

  const rejectMutation = useMutation({
    mutationFn: () => rejectLeave(rejectingId!, reason),
    onSuccess: () => {
      setRejectingId(null);
      setReason('');
      invalidate();
    },
  });

  function renderItem({ item, index }: { item: LeaveRequestInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={styles.headerRow}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.leave_type.name}</Text>
            <StatusBadge value={`${item.total_days}d`} color={item.leave_type.color} uppercase={false} filled />
          </View>
          <Text style={[typography.caption, { color: theme.textMuted }]}>
            {item.start_date} – {item.end_date}
          </Text>
          <Text style={[typography.body, { color: theme.text, marginTop: spacing.xs }]}>{item.reason}</Text>

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
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Leave approvals</Text>
      </View>

      <FlatList
        data={data?.leaves ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={styles.padded}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No pending leave requests.
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
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  actionRow: { flexDirection: 'row', marginTop: spacing.md },
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { borderTopLeftRadius: radii.lg, borderTopRightRadius: radii.lg, padding: spacing.lg },
});
