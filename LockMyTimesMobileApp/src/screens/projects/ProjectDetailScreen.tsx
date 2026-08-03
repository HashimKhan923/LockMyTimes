import { useQuery } from '@tanstack/react-query';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { apiClient } from '../../api/client';
import { Button } from '../../components/common/Button';
import { ProgressRing } from '../../components/common/ProgressRing';
import { Screen } from '../../components/common/Screen';
import { StatTile } from '../../components/common/StatNumber';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TasksStackParamList } from '../../navigation/TasksStack';
import type { ProjectInfo, TaskInfo, TaskStatus } from '../../api/types';

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

interface ProjectShowResponse {
  project: ProjectInfo;
  my_role: string | null;
  my_tasks: TaskInfo[];
  project_stats: { total: number; completed: number; overdue: number };
  my_stats: Record<string, number>;
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

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{data.project.name}</Text>
        <Text style={[typography.caption, { color: theme.textMuted }]}>{data.project.code} · {data.my_role}</Text>

        <View style={{ marginTop: spacing.md }}>
          <Button title="Open board" onPress={() => navigation.navigate('TaskBoard', { id })} />
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
      </View>

      <FlatList
        data={data.my_tasks}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.padded}
        renderItem={({ item, index }) => (
          <MotiView {...entranceStagger(index)}>
            <Pressable
              onPress={() => navigation.navigate('TaskDetail', { id: item.id })}
              style={[
                styles.taskRow,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              <Text style={[typography.body, { color: theme.text, flex: 1 }]}>{item.title}</Text>
              <StatusBadge value={item.status} label={item.status.replace('_', ' ')} color={theme[STATUS_COLOR_KEY[item.status]]} filled />
            </Pressable>
          </MotiView>
        )}
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
  statsRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.md },
  taskRow: { flexDirection: 'row', alignItems: 'center', borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
});
