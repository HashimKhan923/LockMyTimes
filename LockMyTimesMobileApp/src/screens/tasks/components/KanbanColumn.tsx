import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { SharedValue } from 'react-native-reanimated';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import type { TaskInfo, TaskListInfo } from '../../../api/types';
import { KanbanCard, KANBAN_CARD_WIDTH } from './KanbanCard';

interface DragRect {
  x: number;
  y: number;
  width: number;
  height: number;
}

export function KanbanColumn({
  list,
  myEmployeeId,
  isDragging,
  isActiveDropTarget,
  draggedTaskId,
  dragX,
  dragY,
  originX,
  originY,
  registerRef,
  onTaskPress,
  onDragStart,
  onDragUpdate,
  onDragEnd,
}: {
  list: TaskListInfo;
  myEmployeeId: number | null;
  isDragging: boolean;
  isActiveDropTarget: boolean;
  draggedTaskId: number | null;
  dragX: SharedValue<number>;
  dragY: SharedValue<number>;
  originX: SharedValue<number>;
  originY: SharedValue<number>;
  registerRef: (listId: number, ref: View | null) => void;
  onTaskPress: (taskId: number) => void;
  onDragStart: (task: TaskInfo, rect: DragRect) => void;
  onDragUpdate: (absoluteX: number) => void;
  onDragEnd: () => void;
}) {
  const theme = useTheme();
  const overWip = !!list.wip_limit && list.tasks.length > list.wip_limit;

  return (
    <View
      ref={(el) => registerRef(list.id, el)}
      style={[
        styles.column,
        { backgroundColor: theme.surfaceAlt, borderColor: isActiveDropTarget ? theme.primary : 'transparent' },
      ]}
    >
      <View style={[styles.columnAccent, { backgroundColor: list.color ?? theme.primary }]} />
      <View style={styles.columnHeader}>
        <View style={[styles.colorDot, { backgroundColor: list.color ?? theme.primary }]} />
        <Text style={[typography.subheading, { color: theme.text, flex: 1 }]} numberOfLines={1}>
          {list.name}
        </Text>
        <StatusBadge
          value={String(list.tasks.length)}
          color={overWip ? theme.danger : (list.color ?? theme.primary)}
          uppercase={false}
          filled
        />
      </View>
      {overWip && (
        <Text style={[typography.caption, { color: theme.danger, marginHorizontal: spacing.xs, fontSize: 11, fontWeight: '700' }]}>
          Over WIP limit ({list.wip_limit})
        </Text>
      )}

      <ScrollView showsVerticalScrollIndicator={false} scrollEnabled={!isDragging} contentContainerStyle={styles.columnBody}>
        {list.tasks.length === 0 ? (
          <View style={styles.emptyColumn}>
            <Text style={[typography.caption, { color: theme.textMuted }]}>No tasks</Text>
          </View>
        ) : (
          list.tasks.map((task) => (
            <KanbanCard
              key={task.id}
              task={task}
              isMine={task.assignees.some((a) => a.employee_id === myEmployeeId)}
              isBeingDragged={draggedTaskId === task.id}
              dragX={dragX}
              dragY={dragY}
              originX={originX}
              originY={originY}
              onPress={() => onTaskPress(task.id)}
              onDragStart={onDragStart}
              onDragUpdate={onDragUpdate}
              onDragEnd={onDragEnd}
            />
          ))
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  column: {
    width: KANBAN_CARD_WIDTH + spacing.md,
    borderRadius: radii.lg,
    borderWidth: 2,
    padding: spacing.sm,
    marginHorizontal: spacing.xs,
    maxHeight: '100%',
    overflow: 'hidden',
  },
  columnAccent: { position: 'absolute', top: 0, left: 0, right: 0, height: 3 },
  colorDot: { width: 8, height: 8, borderRadius: 4 },
  columnHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    padding: spacing.xs,
    marginTop: spacing.xs,
    marginBottom: spacing.xs,
  },
  columnBody: { paddingBottom: spacing.sm },
  emptyColumn: { alignItems: 'center', paddingVertical: spacing.lg },
});
