import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Modal, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchProjectBoard, moveTask } from '../../api/endpoints/tasks';
import { AvatarStack } from '../../components/common/AvatarStack';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { sheetSpring } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import type { TaskInfo, TaskListInfo, TaskPriority } from '../../api/types';

type Props = NativeStackScreenProps<TasksStackParamList, 'TaskBoard'>;

const PRIORITY_COLOR_KEY: Record<TaskPriority, 'textMuted' | 'primary' | 'warning' | 'danger'> = {
  low: 'textMuted',
  normal: 'primary',
  high: 'warning',
  urgent: 'danger',
};

export function TaskBoardScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;
  const [movingTask, setMovingTask] = useState<TaskInfo | null>(null);

  const { data } = useQuery({
    queryKey: ['projects', 'board', id],
    queryFn: () => fetchProjectBoard(id),
  });

  const totalTasks = (data?.task_lists ?? []).reduce((sum, l) => sum + l.tasks.length, 0);

  const moveMutation = useMutation({
    mutationFn: (taskListId: number) => moveTask(id, movingTask!.id, taskListId),
    onSuccess: () => {
      setMovingTask(null);
      queryClient.invalidateQueries({ queryKey: ['projects', 'board', id] });
    },
  });

  function renderCard(task: TaskInfo, index: number) {
    const priorityColor = theme[PRIORITY_COLOR_KEY[task.priority]];
    return (
      <MotiView
        key={task.id}
        from={{ opacity: 0, translateY: 8 }}
        animate={{ opacity: 1, translateY: 0 }}
        transition={{ ...sheetSpring, delay: index * 30 }}
      >
        <Pressable
          onPress={() => navigation.navigate('TaskDetail', { id: task.id })}
          onLongPress={() => setMovingTask(task)}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={styles.cardTopRow}>
            <View style={[styles.priorityDot, { backgroundColor: priorityColor }]} />
            <Text style={[typography.caption, { color: theme.textMuted, flex: 1 }]}>{task.task_code}</Text>
            {task.comments_count > 0 && (
              <View style={styles.metaChip}>
                <Icon name="chatbubble-outline" size={11} color={theme.textMuted} />
                <Text style={[typography.caption, { color: theme.textMuted, marginLeft: 2 }]}>{task.comments_count}</Text>
              </View>
            )}
          </View>

          <Text style={[typography.body, { color: theme.text, fontWeight: '600', marginTop: 6 }]} numberOfLines={2}>
            {task.title}
          </Text>

          {task.subtasks_count > 0 && (
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: 6 }]}>
              {task.completed_subtasks_count}/{task.subtasks_count} subtasks
            </Text>
          )}

          <View style={styles.cardFooter}>
            {task.due_date ? (
              <View style={[styles.duePill, { backgroundColor: (task.is_overdue ? theme.danger : theme.textMuted) + '17' }]}>
                <Icon name="calendar" size={11} color={task.is_overdue ? theme.danger : theme.textMuted} />
                <Text style={[typography.caption, { color: task.is_overdue ? theme.danger : theme.textMuted, marginLeft: 4, fontWeight: '600' }]}>
                  {new Date(task.due_date + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                </Text>
              </View>
            ) : (
              <View />
            )}
            {task.assignees.length > 0 && <AvatarStack people={task.assignees} size={22} />}
          </View>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <View style={styles.header}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>
          {data?.project.name ?? 'Board'}
        </Text>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
          {totalTasks} task{totalTasks === 1 ? '' : 's'} · {data?.task_lists.length ?? 0} columns
        </Text>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.board}>
        {(data?.task_lists ?? []).map((list: TaskListInfo) => (
          <View key={list.id} style={[styles.column, { backgroundColor: theme.surfaceAlt }]}>
            <View style={[styles.columnAccent, { backgroundColor: list.color ?? theme.primary }]} />
            <View style={styles.columnHeader}>
              <Text style={[typography.subheading, { color: theme.text, flex: 1 }]}>{list.name}</Text>
              <StatusBadge
                value={String(list.tasks.length)}
                color={list.color ?? theme.primary}
                uppercase={false}
                filled
              />
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              {list.tasks.length === 0 ? (
                <View style={styles.emptyColumn}>
                  <Text style={[typography.caption, { color: theme.textMuted }]}>No tasks</Text>
                </View>
              ) : (
                list.tasks.map((task, index) => renderCard(task, index))
              )}
            </ScrollView>
          </View>
        ))}
      </ScrollView>

      <Modal visible={!!movingTask} transparent animationType="fade" onRequestClose={() => setMovingTask(null)}>
        <Pressable style={styles.backdrop} onPress={() => setMovingTask(null)}>
          <View style={[styles.sheet, { backgroundColor: theme.surface }]}>
            <Text style={[typography.subheading, { color: theme.text, marginBottom: spacing.sm }]}>
              Move "{movingTask?.title}" to…
            </Text>
            {(data?.task_lists ?? []).map((list) => (
              <Pressable
                key={list.id}
                onPress={() => moveMutation.mutate(list.id)}
                style={[styles.moveOption, { borderColor: theme.border }]}
              >
                <View style={[styles.moveDot, { backgroundColor: list.color ?? theme.primary }]} />
                <Text style={[typography.body, { color: theme.text }]}>{list.name}</Text>
              </Pressable>
            ))}
          </View>
        </Pressable>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { paddingHorizontal: 24 },
  board: { paddingHorizontal: 16, paddingTop: spacing.md, paddingBottom: spacing.lg, gap: spacing.sm },
  column: {
    width: 270,
    borderRadius: radii.lg,
    padding: spacing.sm,
    marginHorizontal: spacing.xs,
    maxHeight: '100%',
    overflow: 'hidden',
  },
  columnAccent: { position: 'absolute', top: 0, left: 0, right: 0, height: 3 },
  columnHeader: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, padding: spacing.xs, marginTop: spacing.xs, marginBottom: spacing.xs },
  emptyColumn: { alignItems: 'center', paddingVertical: spacing.lg },
  card: { borderRadius: radii.md, padding: spacing.sm + 2, marginBottom: spacing.sm },
  cardTopRow: { flexDirection: 'row', alignItems: 'center' },
  priorityDot: { width: 7, height: 7, borderRadius: 4, marginRight: spacing.xs },
  metaChip: { flexDirection: 'row', alignItems: 'center' },
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.sm },
  duePill: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: spacing.xs + 2, paddingVertical: 3, borderRadius: radii.pill },
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { borderTopLeftRadius: radii.lg, borderTopRightRadius: radii.lg, padding: spacing.lg },
  moveOption: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm + 2, borderTopWidth: StyleSheet.hairlineWidth },
  moveDot: { width: 8, height: 8, borderRadius: 4 },
});
