import { Icon } from '../../components/common/Icon';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Linking, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import {
  addTaskChecklistItem,
  addTaskComment,
  fetchTask,
  toggleTaskChecklistItem,
  updateTaskStatus,
} from '../../api/endpoints/tasks';
import { Button } from '../../components/common/Button';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import type { TaskStatus } from '../../api/types';

type Props = NativeStackScreenProps<TasksStackParamList, 'TaskDetail'>;

const STATUSES: TaskStatus[] = ['todo', 'in_progress', 'in_review', 'on_hold', 'done'];

const STATUS_COLOR_KEY: Record<TaskStatus, 'textMuted' | 'primary' | 'warning' | 'danger' | 'success'> = {
  backlog: 'textMuted',
  todo: 'textMuted',
  in_progress: 'primary',
  in_review: 'warning',
  on_hold: 'danger',
  done: 'success',
  cancelled: 'textMuted',
};

export function TaskDetailScreen({ route }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;
  const [comment, setComment] = useState('');
  const [checklistItem, setChecklistItem] = useState('');

  const { data, isLoading } = useQuery({ queryKey: ['tasks', 'detail', id], queryFn: () => fetchTask(id) });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['tasks'] });

  const statusMutation = useMutation({
    mutationFn: (status: TaskStatus) => updateTaskStatus(id, status),
    onSuccess: invalidate,
  });

  const commentMutation = useMutation({
    mutationFn: () => addTaskComment(id, comment),
    onSuccess: () => {
      setComment('');
      invalidate();
    },
  });

  const checklistMutation = useMutation({
    mutationFn: () => addTaskChecklistItem(id, checklistItem),
    onSuccess: () => {
      setChecklistItem('');
      invalidate();
    },
  });

  const toggleMutation = useMutation({
    mutationFn: (checklistId: number) => toggleTaskChecklistItem(id, checklistId),
    onSuccess: invalidate,
  });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const { task, can_edit } = data;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>{task.task_code}</Text>
        <Text style={[typography.title, { color: theme.text }]}>{task.title}</Text>
        {task.description && (
          <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs }]}>{task.description}</Text>
        )}

        {can_edit && (
          <View style={styles.statusRow}>
            {STATUSES.map((s) => {
              const active = task.status === s;
              const color = theme[STATUS_COLOR_KEY[s]];
              return (
                <Pressable
                  key={s}
                  onPress={() => statusMutation.mutate(s)}
                  style={[
                    styles.statusChip,
                    { backgroundColor: active ? color : color + '1A', borderColor: active ? color : theme.border },
                  ]}
                >
                  <Text style={{ color: active ? theme.onPrimary : color, fontWeight: '600', fontSize: 12 }}>
                    {s.replace('_', ' ')}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        )}

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <View style={styles.checklistHeader}>
            <ProgressRing
              percent={data.checklist_total > 0 ? (data.checklist_done / data.checklist_total) * 100 : 0}
              size={48}
              strokeWidth={5}
              gradientColors={theme.gradients.success}
            />
            <Text style={[typography.subheading, { color: theme.text, marginLeft: spacing.sm }]}>
              Checklist ({data.checklist_done}/{data.checklist_total})
            </Text>
          </View>
          {(task.checklists ?? []).map((c) => (
            <Pressable key={c.id} onPress={() => toggleMutation.mutate(c.id)} style={styles.checklistRow}>
              <Icon
                name={c.is_completed ? 'checkbox' : 'square-outline'}
                size={20}
                color={c.is_completed ? theme.success : theme.textMuted}
              />
              <Text
                style={[
                  typography.body,
                  { color: theme.text, marginLeft: spacing.sm, textDecorationLine: c.is_completed ? 'line-through' : 'none' },
                ]}
              >
                {c.item}
              </Text>
            </Pressable>
          ))}
          <View style={styles.addRow}>
            <View style={{ flex: 1 }}>
              <TextField label="" placeholder="Add checklist item" value={checklistItem} onChangeText={setChecklistItem} />
            </View>
            <Pressable
              onPress={() => checklistItem.trim() && checklistMutation.mutate()}
              style={[styles.addButton, { backgroundColor: theme.primary }]}
            >
              <Icon name="add" size={20} color={theme.onPrimary} />
            </Pressable>
          </View>
        </View>

        {(task.attachments?.length ?? 0) > 0 && (
          <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Attachments</Text>
            {task.attachments!.map((a) => (
              <Pressable key={a.id} onPress={() => Linking.openURL(a.url)} style={styles.attachmentRow}>
                <Icon name="document-attach-outline" size={18} color={theme.primary} />
                <Text style={[typography.body, { color: theme.primary, marginLeft: spacing.sm }]}>{a.file_name}</Text>
              </Pressable>
            ))}
          </View>
        )}

        <View style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}>
          <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>Comments</Text>
          {(task.comments ?? []).map((c) => (
            <View key={c.id} style={styles.commentRow}>
              <Text style={[typography.caption, { color: theme.text, fontWeight: '700' }]}>{c.user_name}</Text>
              <Text style={[typography.body, { color: theme.text }]}>{c.content}</Text>
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                {new Date(c.created_at).toLocaleString()}
              </Text>
            </View>
          ))}
          <View style={styles.addRow}>
            <View style={{ flex: 1 }}>
              <TextField label="" placeholder="Write a comment" value={comment} onChangeText={setComment} />
            </View>
            <Pressable
              onPress={() => comment.trim() && commentMutation.mutate()}
              style={[styles.addButton, { backgroundColor: theme.primary }]}
            >
              <Icon name="send" size={18} color={theme.onPrimary} />
            </Pressable>
          </View>
        </View>

        <View style={{ height: spacing.xl }} />
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  statusRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginTop: spacing.md },
  statusChip: { paddingHorizontal: spacing.sm + 2, paddingVertical: spacing.xs + 2, borderRadius: radii.pill, borderWidth: 1 },
  card: { borderRadius: radii.xl, padding: spacing.md, marginTop: spacing.md },
  checklistHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.sm },
  checklistRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  addRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.xs },
  addButton: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
  attachmentRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: spacing.xs + 2 },
  commentRow: { paddingVertical: spacing.sm, borderBottomWidth: StyleSheet.hairlineWidth, borderColor: 'rgba(128,128,128,0.2)' },
});
