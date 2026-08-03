import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRef, useState } from 'react';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue } from 'react-native-reanimated';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchProjectBoard, moveTask } from '../../api/endpoints/tasks';
import { AvatarStack } from '../../components/common/AvatarStack';
import { Screen } from '../../components/common/Screen';
import { StatTile } from '../../components/common/StatNumber';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useAuthStore } from '../../stores/authStore';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import type { TaskInfo, TaskListInfo } from '../../api/types';
import { KanbanCardContent } from './components/KanbanCard';
import { KanbanColumn } from './components/KanbanColumn';

type Props = NativeStackScreenProps<TasksStackParamList, 'TaskBoard'>;

interface DragRect {
  x: number;
  y: number;
  width: number;
  height: number;
}

export function TaskBoardScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;
  const myEmployeeId = useAuthStore((s) => s.user?.employee_id ?? null);

  const { data } = useQuery({
    queryKey: ['projects', 'board', id],
    queryFn: () => fetchProjectBoard(id),
  });

  const totalTasks = (data?.task_lists ?? []).reduce((sum, l) => sum + l.tasks.length, 0);

  /* ───────── Drag-and-drop state ─────────
   * dragX/dragY track the floating ghost card's on-screen top-left corner;
   * originX/originY freeze where the drag began so onUpdate can add the
   * gesture's translation to a fixed point instead of re-measuring every
   * frame. Column window bounds are captured once at drag-start (scrolling
   * is frozen for the duration of a drag) rather than continuously, since
   * ScrollView position changes don't fire onLayout.
   */
  const dragX = useSharedValue(0);
  const dragY = useSharedValue(0);
  const originX = useSharedValue(0);
  const originY = useSharedValue(0);

  const [draggedTask, setDraggedTask] = useState<TaskInfo | null>(null);
  const [activeDropListId, setActiveDropListId] = useState<number | null>(null);
  const dragOriginListIdRef = useRef<number | null>(null);
  const activeDropListIdRef = useRef<number | null>(null);
  const columnRefs = useRef<Record<number, View | null>>({});
  const columnBoundsRef = useRef<Record<number, { x: number; width: number }>>({});

  const moveMutation = useMutation({
    mutationFn: ({ taskId, listId }: { taskId: number; listId: number }) => moveTask(id, taskId, listId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects', 'board', id] });
    },
  });

  function registerColumnRef(listId: number, ref: View | null) {
    columnRefs.current[listId] = ref;
  }

  function handleDragStart(task: TaskInfo, _rect: DragRect) {
    setDraggedTask(task);
    dragOriginListIdRef.current = task.task_list_id;
    setActiveDropListId(task.task_list_id);
    activeDropListIdRef.current = task.task_list_id;

    Object.entries(columnRefs.current).forEach(([listId, ref]) => {
      ref?.measureInWindow((x, _y, width) => {
        columnBoundsRef.current[Number(listId)] = { x, width };
      });
    });
  }

  function handleDragUpdate(absoluteX: number) {
    const match = Object.entries(columnBoundsRef.current).find(
      ([, bounds]) => absoluteX >= bounds.x && absoluteX <= bounds.x + bounds.width
    );
    const matchedId = match ? Number(match[0]) : null;
    if (matchedId !== activeDropListIdRef.current) {
      activeDropListIdRef.current = matchedId;
      setActiveDropListId(matchedId);
    }
  }

  function handleDragEnd() {
    const originListId = dragOriginListIdRef.current;
    const targetListId = activeDropListIdRef.current ?? originListId;
    const task = draggedTask;

    setDraggedTask(null);
    setActiveDropListId(null);
    dragOriginListIdRef.current = null;
    activeDropListIdRef.current = null;

    if (task && targetListId && targetListId !== originListId) {
      moveMutation.mutate({ taskId: task.id, listId: targetListId });
    }
  }

  const ghostStyle = useAnimatedStyle(() => ({
    left: dragX.value,
    top: dragY.value,
  }));

  return (
    <Screen padded={false}>
      <View style={styles.header}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>
          {data?.project.name ?? 'Board'}
        </Text>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
          {data?.project.code} · {totalTasks} task{totalTasks === 1 ? '' : 's'} · {data?.task_lists.length ?? 0} columns
        </Text>

        <View style={styles.statsRow}>
          <StatTile value={data?.task_stats.done ?? 0} label="Done" color={theme.success} />
          <StatTile value={data?.task_stats.in_progress ?? 0} label="In progress" color={theme.primary} />
          <StatTile value={data?.task_stats.overdue ?? 0} label="Overdue" color={theme.danger} />
          {(data?.members.length ?? 0) > 0 && (
            <View
              style={[
                styles.membersTile,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              <AvatarStack people={data!.members} size={22} max={4} />
            </View>
          )}
        </View>
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        scrollEnabled={!draggedTask}
        contentContainerStyle={styles.board}
      >
        {(data?.task_lists ?? []).map((list: TaskListInfo) => (
          <KanbanColumn
            key={list.id}
            list={list}
            myEmployeeId={myEmployeeId}
            isDragging={!!draggedTask}
            isActiveDropTarget={activeDropListId === list.id}
            draggedTaskId={draggedTask?.id ?? null}
            dragX={dragX}
            dragY={dragY}
            originX={originX}
            originY={originY}
            registerRef={registerColumnRef}
            onTaskPress={(taskId) => navigation.navigate('TaskDetail', { id: taskId })}
            onDragStart={handleDragStart}
            onDragUpdate={handleDragUpdate}
            onDragEnd={handleDragEnd}
          />
        ))}
      </ScrollView>

      {draggedTask && (
        <Animated.View pointerEvents="none" style={[styles.ghost, ghostStyle]}>
          <KanbanCardContent
            task={draggedTask}
            isMine={draggedTask.assignees.some((a) => a.employee_id === myEmployeeId)}
          />
        </Animated.View>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { paddingHorizontal: 24 },
  statsRow: { flexDirection: 'row', alignItems: 'stretch', gap: spacing.sm, marginTop: spacing.md },
  membersTile: {
    borderRadius: radii.md,
    paddingHorizontal: spacing.sm,
    alignItems: 'center',
    justifyContent: 'center',
  },
  board: { paddingHorizontal: 16, paddingTop: spacing.md, paddingBottom: spacing.lg, gap: spacing.sm },
  ghost: {
    position: 'absolute',
    zIndex: 999,
    elevation: 12,
    shadowColor: '#08071A',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.35,
    shadowRadius: 22,
    transform: [{ scale: 1.03 }, { rotate: '2deg' }],
  },
});
