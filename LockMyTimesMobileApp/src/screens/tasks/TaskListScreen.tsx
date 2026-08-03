import { useQuery } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { AvatarStack } from '../../components/common/AvatarStack';
import { Button } from '../../components/common/Button';
import { DateStrip, type DateStripDay } from '../../components/common/DateStrip';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTasks } from '../../api/endpoints/tasks';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useAuthStore } from '../../stores/authStore';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import { useResetOnTabBlur } from '../../navigation/useResetOnTabBlur';
import type { TaskInfo } from '../../api/types';
import { PRIORITY_META, taskTypeMeta } from './taskDisplay';

type Props = NativeStackScreenProps<TasksStackParamList, 'TaskList'>;

const FILTERS = ['open', 'todo', 'in_progress', 'in_review', 'done', 'overdue', 'all'] as const;

function toISODate(d: Date) {
  return d.toISOString().slice(0, 10);
}

function buildWeek(centerOffsetStart = -3, count = 7): DateStripDay[] {
  const today = new Date();
  const days: DateStripDay[] = [];
  for (let i = 0; i < count; i++) {
    const d = new Date(today);
    d.setDate(today.getDate() + centerOffsetStart + i);
    days.push({
      date: toISODate(d),
      weekdayLabel: d.toLocaleDateString(undefined, { weekday: 'short' }).slice(0, 3),
      dayNumber: String(d.getDate()),
    });
  }
  return days;
}

export function TaskListScreen({ navigation }: Props) {
  useResetOnTabBlur(navigation);
  const theme = useTheme();
  const myEmployeeId = useAuthStore((s) => s.user?.employee_id ?? null);
  const [filter, setFilter] = useState<(typeof FILTERS)[number]>('open');
  const week = useMemo(() => buildWeek(), []);
  const [selectedDate, setSelectedDate] = useState<string | null>(toISODate(new Date()));

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['tasks', filter],
    queryFn: () => fetchTasks({ filter }),
  });

  const visibleTasks = useMemo(() => {
    const all = data?.tasks ?? [];
    if (!selectedDate) return all;
    return all.filter((t) => t.due_date?.slice(0, 10) === selectedDate);
  }, [data, selectedDate]);

  function renderTask({ item, index }: { item: TaskInfo; index: number }) {
    const cardAccent = item.project?.color ?? theme.categorical[item.project_id % theme.categorical.length];
    const type = taskTypeMeta(item.type);
    const priority = PRIORITY_META[item.priority];
    const isMine = item.assignees.some((a) => a.employee_id === myEmployeeId);

    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('TaskDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: theme.surface, borderLeftColor: cardAccent },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={styles.metaRow}>
            <StatusBadge value={item.type} label={type.label} color={type.color} filled uppercase={false} />
            <StatusBadge value={item.priority} label={priority.label} color={theme[priority.colorKey]} filled uppercase={false} />
            {isMine && (
              <View style={[styles.youBadge, { backgroundColor: theme.primary + '22' }]}>
                <Text style={[styles.youBadgeText, { color: theme.primary }]}>YOU</Text>
              </View>
            )}
          </View>

          <View style={styles.cardTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]} numberOfLines={1}>
                {item.title}
              </Text>
              <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]} numberOfLines={1}>
                {item.project?.name ?? item.task_code}
              </Text>
            </View>
            <Text style={[typography.subheading, { color: cardAccent, marginLeft: spacing.sm }]}>{item.progress}%</Text>
            <Icon name="ellipsis-vertical" size={18} color={theme.textMuted} style={{ marginLeft: spacing.xs }} />
          </View>

          <View style={[styles.progressTrack, { backgroundColor: theme.surfaceAlt }]}>
            <View style={[styles.progressFill, { width: `${Math.max(4, item.progress)}%`, backgroundColor: cardAccent }]} />
          </View>

          {(item.comments_count > 0 || item.subtasks_count > 0 || item.estimated_hours > 0) && (
            <View style={styles.chipsRow}>
              {item.comments_count > 0 && (
                <View style={styles.metaChip}>
                  <Icon name="chatbubble-outline" size={12} color={theme.textMuted} />
                  <Text style={[styles.metaChipText, { color: theme.textMuted }]}>{item.comments_count}</Text>
                </View>
              )}
              {item.subtasks_count > 0 && (
                <View style={styles.metaChip}>
                  <Icon
                    name="checkbox-outline"
                    size={12}
                    color={item.completed_subtasks_count >= item.subtasks_count ? theme.success : theme.textMuted}
                  />
                  <Text
                    style={[
                      styles.metaChipText,
                      { color: item.completed_subtasks_count >= item.subtasks_count ? theme.success : theme.textMuted },
                    ]}
                  >
                    {item.completed_subtasks_count}/{item.subtasks_count}
                  </Text>
                </View>
              )}
              {item.estimated_hours > 0 && (
                <View style={styles.metaChip}>
                  <Icon name="time" size={12} color={theme.textMuted} />
                  <Text style={[styles.metaChipText, { color: theme.textMuted }]}>{item.estimated_hours}h</Text>
                </View>
              )}
            </View>
          )}

          <View style={styles.cardFooterRow}>
            <View style={styles.footerLeft}>
              <Icon name="calendar" size={14} color={item.is_overdue ? theme.danger : theme.textMuted} />
              <Text style={[typography.caption, { color: item.is_overdue ? theme.danger : theme.textMuted, marginLeft: 4, fontWeight: item.is_overdue ? '700' : '400' }]}>
                {item.due_date ? new Date(item.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : 'No due date'}
              </Text>
            </View>
            {item.assignees.length > 0 && <AvatarStack people={item.assignees} size={24} />}
          </View>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.headerRow}>
          <View>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Tasks</Text>
            {data && (
              <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)', marginTop: spacing.xs }]}>
                {data.today_due} due today · {data.this_week_due} due this week
              </Text>
            )}
          </View>
          <Button
            title="Add Task"
            variant="accent"
            icon="add"
            compact
            onPress={() => (data?.my_projects[0] ? navigation.navigate('TaskBoard', { id: data.my_projects[0].id }) : undefined)}
          />
        </View>
      </HeroHeader>

      <View style={styles.padded}>
        <View style={{ marginTop: spacing.md }}>
          <DateStrip days={week} selected={selectedDate} onSelect={setSelectedDate} />
        </View>

        {(data?.my_projects.length ?? 0) > 0 && (
          <FlatList
            horizontal
            showsHorizontalScrollIndicator={false}
            data={data!.my_projects}
            keyExtractor={(p) => String(p.id)}
            style={{ marginTop: spacing.md }}
            contentContainerStyle={{ gap: spacing.sm }}
            renderItem={({ item: p }) => (
              <Pressable
                onPress={() => navigation.navigate('ProjectDetail', { id: p.id })}
                style={[styles.projectChip, { backgroundColor: p.color, borderColor: theme.border }]}
              >
                <Text style={{ color: '#fff', fontWeight: '700' }}>{p.name}</Text>
              </Pressable>
            )}
          />
        )}

        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={FILTERS}
          keyExtractor={(f) => f}
          style={{ marginTop: spacing.md }}
          contentContainerStyle={{ gap: spacing.sm }}
          renderItem={({ item: f }) => (
            <Pressable onPress={() => setFilter(f)}>
              {filter === f ? (
                <LinearGradient colors={theme.gradients.accent} style={styles.chip}>
                  <Text style={{ color: '#fff', fontWeight: '700' }}>{f.replace('_', ' ')}</Text>
                </LinearGradient>
              ) : (
                <View style={[styles.chip, { backgroundColor: theme.surfaceAlt, borderColor: theme.border, borderWidth: 1 }]}>
                  <Text style={{ color: theme.text, fontWeight: '600' }}>{f.replace('_', ' ')}</Text>
                </View>
              )}
            </Pressable>
          )}
        />
      </View>

      <FlatList
        data={visibleTasks}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderTask}
        contentContainerStyle={[styles.padded, { paddingTop: spacing.md, paddingBottom: spacing.xxl }]}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No tasks here.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end' },
  chip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill },
  projectChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  card: {
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
    borderLeftWidth: 4,
  },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginBottom: spacing.xs },
  youBadge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: radii.pill },
  youBadgeText: { fontSize: 9, fontWeight: '900' },
  cardTopRow: { flexDirection: 'row', alignItems: 'center' },
  progressTrack: { height: 6, borderRadius: 3, marginTop: spacing.sm, overflow: 'hidden' },
  progressFill: { height: 6, borderRadius: 3 },
  chipsRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.sm },
  metaChip: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  metaChipText: { fontSize: 11, fontWeight: '600' },
  cardFooterRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.sm },
  footerLeft: { flexDirection: 'row', alignItems: 'center' },
});