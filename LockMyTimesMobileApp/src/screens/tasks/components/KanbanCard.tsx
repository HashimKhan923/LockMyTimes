import { useMemo, useRef } from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import Animated, {
  runOnJS,
  useAnimatedStyle,
  useSharedValue,
  withTiming,
  type SharedValue,
} from 'react-native-reanimated';
import { AvatarStack } from '../../../components/common/AvatarStack';
import { Icon } from '../../../components/common/Icon';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { elevatedShadow, radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import type { TaskInfo } from '../../../api/types';
import { PRIORITY_META, taskTypeMeta } from '../taskDisplay';

export const KANBAN_CARD_WIDTH = 240;

interface DragRect {
  x: number;
  y: number;
  width: number;
  height: number;
}

/** Pure visual content — reused by both the in-column card and the floating drag ghost. */
export function KanbanCardContent({ task, isMine }: { task: TaskInfo; isMine: boolean }) {
  const theme = useTheme();
  const type = taskTypeMeta(task.type);
  const priority = PRIORITY_META[task.priority];

  return (
    <View
      style={[
        styles.card,
        { backgroundColor: theme.surface, borderLeftColor: theme[priority.colorKey] },
      ]}
    >
      <View style={styles.topRow}>
        <View style={styles.topRowLeft}>
          <StatusBadge value={task.type} label={type.label} color={type.color} filled uppercase={false} />
          {isMine && (
            <View style={[styles.youBadge, { backgroundColor: theme.primary + '22' }]}>
              <Text style={[styles.youBadgeText, { color: theme.primary }]}>YOU</Text>
            </View>
          )}
        </View>
        {task.due_date ? (
          <View style={styles.dueRow}>
            <Icon name="calendar" size={11} color={task.is_overdue ? theme.danger : theme.textMuted} />
            <Text
              style={[
                typography.caption,
                { color: task.is_overdue ? theme.danger : theme.textMuted, marginLeft: 3, fontWeight: '700', fontSize: 11 },
              ]}
            >
              {new Date(task.due_date + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
            </Text>
          </View>
        ) : null}
      </View>

      <Text style={[typography.body, { color: theme.text, fontWeight: '600', marginTop: spacing.xs + 2 }]} numberOfLines={2}>
        {task.title}
      </Text>

      <View style={styles.subMetaRow}>
        <Text style={[typography.caption, { color: theme.textMuted, fontSize: 11 }]}>{task.task_code}</Text>
        {task.priority === 'urgent' && (
          <View style={styles.urgentTag}>
            <Icon name="lightning" size={10} color={theme.danger} />
            <Text style={[styles.urgentTagText, { color: theme.danger }]}>Urgent</Text>
          </View>
        )}
      </View>

      {task.progress > 0 && (
        <View style={[styles.progressTrack, { backgroundColor: theme.surfaceAlt }]}>
          <View style={[styles.progressFill, { width: `${Math.min(100, task.progress)}%`, backgroundColor: type.color }]} />
        </View>
      )}

      <View style={styles.footer}>
        {task.assignees.length > 0 ? <AvatarStack people={task.assignees} size={22} /> : <View />}
        <View style={styles.metaChips}>
          {task.comments_count > 0 && (
            <View style={styles.metaChip}>
              <Icon name="chatbubble-outline" size={11} color={theme.textMuted} />
              <Text style={[styles.metaChipText, { color: theme.textMuted }]}>{task.comments_count}</Text>
            </View>
          )}
          {task.subtasks_count > 0 && (
            <View style={styles.metaChip}>
              <Icon
                name="checkbox-outline"
                size={11}
                color={task.completed_subtasks_count >= task.subtasks_count ? theme.success : theme.textMuted}
              />
              <Text
                style={[
                  styles.metaChipText,
                  { color: task.completed_subtasks_count >= task.subtasks_count ? theme.success : theme.textMuted },
                ]}
              >
                {task.completed_subtasks_count}/{task.subtasks_count}
              </Text>
            </View>
          )}
          {task.estimated_hours > 0 && (
            <View style={styles.metaChip}>
              <Icon name="time" size={11} color={theme.textMuted} />
              <Text style={[styles.metaChipText, { color: theme.textMuted }]}>{task.estimated_hours}h</Text>
            </View>
          )}
        </View>
      </View>
    </View>
  );
}

export function KanbanCard({
  task,
  isMine,
  isBeingDragged,
  dragX,
  dragY,
  originX,
  originY,
  onPress,
  onDragStart,
  onDragUpdate,
  onDragEnd,
}: {
  task: TaskInfo;
  isMine: boolean;
  isBeingDragged: boolean;
  dragX: SharedValue<number>;
  dragY: SharedValue<number>;
  originX: SharedValue<number>;
  originY: SharedValue<number>;
  onPress: () => void;
  onDragStart: (task: TaskInfo, rect: DragRect) => void;
  onDragUpdate: (absoluteX: number) => void;
  onDragEnd: () => void;
}) {
  const cardRef = useRef<View>(null);
  const pressScale = useSharedValue(1);
  const lastCheckX = useSharedValue(0);

  // Callbacks change every render (fresh closures over task/state) but the
  // Gesture object below must NOT be recreated mid-drag — a parent re-render
  // (e.g. from the active-column highlight changing) would otherwise tear
  // down and rebuild the gesture while a finger is still on screen. Routing
  // through a ref keeps the Gesture stable while callbacks stay fresh.
  const latest = useRef({ onPress, onDragStart, onDragUpdate, onDragEnd });
  latest.current = { onPress, onDragStart, onDragUpdate, onDragEnd };

  function measureAndStart() {
    cardRef.current?.measureInWindow((x, y, width, height) => {
      originX.value = x;
      originY.value = y;
      dragX.value = x;
      dragY.value = y;
      latest.current.onDragStart(task, { x, y, width, height });
    });
  }

  const forwardStart = useRef(() => measureAndStart()).current;
  const forwardUpdate = useRef((x: number) => latest.current.onDragUpdate(x)).current;
  const forwardEnd = useRef(() => latest.current.onDragEnd()).current;
  const forwardTap = useRef(() => latest.current.onPress()).current;

  const gesture = useMemo(() => {
    const pan = Gesture.Pan()
      .activateAfterLongPress(280)
      .onBegin(() => {
        pressScale.value = withTiming(0.97, { duration: 150 });
      })
      .onStart(() => {
        runOnJS(forwardStart)();
      })
      .onUpdate((e) => {
        dragX.value = originX.value + e.translationX;
        dragY.value = originY.value + e.translationY;
        if (Math.abs(e.absoluteX - lastCheckX.value) > 12) {
          lastCheckX.value = e.absoluteX;
          runOnJS(forwardUpdate)(e.absoluteX);
        }
      })
      .onEnd(() => {
        runOnJS(forwardEnd)();
      })
      .onFinalize(() => {
        pressScale.value = withTiming(1, { duration: 150 });
      });

    const tap = Gesture.Tap()
      .maxDuration(250)
      .onEnd((_e, success) => {
        if (success) runOnJS(forwardTap)();
      });

    return Gesture.Race(tap, pan);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: pressScale.value }],
    opacity: isBeingDragged ? 0.3 : 1,
  }));

  return (
    <GestureDetector gesture={gesture}>
      <Animated.View
        ref={cardRef}
        style={[
          animatedStyle,
          Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
        ]}
      >
        <KanbanCardContent task={task} isMine={isMine} />
      </Animated.View>
    </GestureDetector>
  );
}

const styles = StyleSheet.create({
  card: {
    width: KANBAN_CARD_WIDTH,
    borderRadius: radii.md,
    borderLeftWidth: 3,
    padding: spacing.sm + 2,
    marginBottom: spacing.sm,
  },
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  topRowLeft: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, flexShrink: 1 },
  youBadge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: radii.pill },
  youBadgeText: { fontSize: 9, fontWeight: '900' },
  dueRow: { flexDirection: 'row', alignItems: 'center' },
  subMetaRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 4 },
  urgentTag: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  urgentTagText: { fontSize: 10, fontWeight: '800' },
  progressTrack: { height: 4, borderRadius: 2, marginTop: spacing.xs + 2, overflow: 'hidden' },
  progressFill: { height: 4, borderRadius: 2 },
  footer: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.sm },
  metaChips: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  metaChip: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  metaChipText: { fontSize: 11, fontWeight: '600' },
});
