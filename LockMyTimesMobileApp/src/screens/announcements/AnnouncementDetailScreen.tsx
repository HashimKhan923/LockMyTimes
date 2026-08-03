import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { acknowledgeAnnouncement, fetchAnnouncement } from '../../api/endpoints/announcements';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AnnouncementsStackParamList } from '../../navigation/AnnouncementsStack';

type Props = NativeStackScreenProps<AnnouncementsStackParamList, 'AnnouncementDetail'>;

export function AnnouncementDetailScreen({ route }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;

  const { data, isLoading } = useQuery({
    queryKey: ['announcements', 'detail', id],
    queryFn: () => fetchAnnouncement(id),
  });

  const ackMutation = useMutation({
    mutationFn: () => acknowledgeAnnouncement(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['announcements'] });
      queryClient.invalidateQueries({ queryKey: ['announcements', 'detail', id] });
    },
  });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const a = data.announcement;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
          {a.creator_name} · {a.published_at ? new Date(a.published_at).toLocaleDateString() : ''}
        </Text>
        <Text style={[typography.title, { color: theme.text }]}>{a.title}</Text>
        <Text style={[typography.body, { color: theme.text, marginTop: spacing.md, lineHeight: 22 }]}>{a.content}</Text>

        {a.requires_acknowledgment && (
          <View style={[styles.card, { backgroundColor: theme.surfaceAlt }]}>
            {a.is_acknowledged ? (
              <StatusBadge value="acknowledged" label="Acknowledged" color={theme.success} filled />
            ) : (
              <>
                <View style={{ marginBottom: spacing.sm, alignSelf: 'flex-start' }}>
                  <StatusBadge value="pending" label="Acknowledgment required" color={theme.warning} filled />
                </View>
                <Button title="Acknowledge" onPress={() => ackMutation.mutate()} loading={ackMutation.isPending} />
              </>
            )}
          </View>
        )}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.lg },
});
