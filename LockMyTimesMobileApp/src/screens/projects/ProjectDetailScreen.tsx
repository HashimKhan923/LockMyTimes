import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { apiClient } from '../../api/client';
import { AvatarStack } from '../../components/common/AvatarStack';
import { Button } from '../../components/common/Button';
import { Icon } from '../../components/common/Icon';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatTile } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import type { ProjectInfo, ProjectMemberInfo, TaskInfo, TaskStatus } from '../../api/types';
import { taskTypeMeta } from '../tasks/taskDisplay';

type Props = NativeStackScreenProps<TasksStackParamList, 'ProjectDetail'>;

const STATUS_COLOR_KEY: Record<TaskStatus, 'textMuted' | 'primary' | 'warning' | 'danger' | 'success'> = {
  backlog: 'textMuted',
  todo: 'textMuted',
  in_progress: 'primary',
  in_review: 'warning',
  on_hold: 'danger',
  done: 'success',
  cancelled: 'textMuted',
};

const MY_STATS_META: { key: string; label: string; colorKey: 'textMuted' | 'primary' | 'warning' | 'danger' | 'success' }[] = [
  { key: 'todo', label: 'To do', colorKey: 'textMuted' },
  { key: 'in_progress', label: 'In progress', colorKey: 'primary' },
  { key: 'in_review', label: 'In review', colorKey: 'warning' },
  { key: 'on_hold', label: 'On hold', colorKey: 'danger' },
  { key: 'done', label: 'Done', colorKey: 'success' },
];

interface ProjectShowResponse {
  project: ProjectInfo;
  my_role: string | null;
  my_tasks: TaskInfo[];
  project_stats: { total: number; completed: number; overdue: number };
  my_stats: Record<string, number>;
  members: ProjectMemberInfo[];
}

async function fetchProjectDetail(id: number): Promise<ProjectShowResponse> {
  const { data } = await apiClient.get(`/projects/${id}`);
  return data;
}

export function ProjectDetailScreen({ route, navigation }: Props) {
  const theme = useTheme();
  const { id } = route.params;

  const { data, isLoading } = useQuery({
    queryKey: ['projects', 'detail', id],
    queryFn: () => fetchProjectDetail(id),
  });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const myStatsChips = MY_STATS_META.map((m) => ({ ...m, value: data.my_stats[m.key] ?? 0 })).filter((m) => m.value > 0);

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <View style={styles.titleRow}>
          <View style={[styles.projectDot, { backgroundColor: data.project.color }]} />
          <View style={{ flex: 1 }}>
            <Text style={[typography.title, { color: theme.text }]}>{data.project.name}</Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {data.project.code} {data.my_role ? `· ${data.my_role}` : ''}
            </Text>
          </View>
        </View>

        {data.project.description && (
          <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.sm }]} numberOfLines={3}>
            {data.project.description}
          </Text>
        )}

        {data.members.length > 0 && (
          <View style={styles.membersRow}>
            <AvatarStack people={data.members} size={26} max={6} />
            <Text style={[typography.caption, { color: theme.textMuted, marginLeft: spacing.sm }]}>
              {data.members.length} member{data.members.length === 1 ? '' : 's'}
            </Text>
          </View>
        )}

        <View style={{ marginTop: spacing.md }}>
          <Button title="Open board" icon="grid" onPress={() => navigation.navigate('TaskBoard', { id })} />
        </View>

        <View style={styles.statsRow}>
          <ProgressRing
            percent={data.project_stats.total > 0 ? (data.project_stats.completed / data.project_stats.total) * 100 : 0}
            size={72}
            strokeWidth={7}
            gradientColors={theme.gradients.success}
          >
            <Text style={[typography.caption, { color: theme.text, fontWeight: '700' }]}>
              {data.project_stats.completed}/{data.project_stats.total}
            </Text>
          </ProgressRing>
          <StatTile label="Total" value={data.project_stats.total} />
          <StatTile label="Completed" value={data.project_stats.completed} color={theme.success} />
          <StatTile label="Overdue" value={data.project_stats.overdue} color={theme.danger} />
        </View>

        {myStatsChips.length > 0 && (
          <View style={styles.chipsRow}>
            {myStatsChips.map((c) => (
              <StatusBadge key={c.key} value={c.key} label={`${c.value} ${c.label}`} color={theme[c.colorKey]} filled uppercase={false} />
            ))}
          </View>
        )}

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg, marginBottom: spacing.xs }]}>
          My Tasks
        </Text>
      </View>

      <FlatList
        data={data.my_tasks}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.padded}
        renderItem={({ item, index }) => {
          const type = taskTypeMeta(item.type);
          return (
            <MotiView {...entranceStagger(index)}>
              <Pressable
                onPress={() => navigation.navigate('TaskDetail', { id: item.id })}
                style={[
                  styles.taskRow,
                  { backgroundColor: theme.surface, borderLeftColor: theme[STATUS_COLOR_KEY[item.status]] },
                  Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
                ]}
              >
                <View style={{ flex: 1 }}>
                  <View style={styles.taskMetaRow}>
                    <StatusBadge value={item.type} label={type.label} color={type.color} filled uppercase={false} />
                    {item.due_date && (
                      <View style={styles.dueRow}>
                        <Icon name="calendar" size={11} color={item.is_overdue ? theme.danger : theme.textMuted} />
                        <Text style={[typography.caption, { color: item.is_overdue ? theme.danger : theme.textMuted, marginLeft: 3, fontSize: 11, fontWeight: '600' }]}>
                          {new Date(item.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                        </Text>
                      </View>
                    )}
                  </View>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '600', marginTop: 4 }]} numberOfLines={2}>
                    {item.title}
                  </Text>
                  <View style={styles.taskFooterRow}>
                    {item.assignees.length > 0 ? <AvatarStack people={item.assignees} size={20} /> : <View />}
                    <StatusBadge value={item.status} label={item.status.replace('_', ' ')} color={theme[STATUS_COLOR_KEY[item.status]]} filled />
                  </View>
                </View>
              </Pressable>
            </MotiView>
          );
        }}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
            You have no tasks in this project.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  padded: { paddingHorizontal: 24 },
  titleRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginTop: spacing.md },
  projectDot: { width: 12, height: 12, borderRadius: 6, marginTop: 8 },
  membersRow: { flexDirection: 'row', alignItems: 'center', marginTop: spacing.sm },
  statsRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.md },
  chipsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginTop: spacing.md },
  taskRow: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm, borderLeftWidth: 3 },
  taskMetaRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  dueRow: { flexDirection: 'row', alignItems: 'center' },
  taskFooterRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.sm },
});
