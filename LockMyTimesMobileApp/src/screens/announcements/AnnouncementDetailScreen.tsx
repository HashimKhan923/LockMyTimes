import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Image, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { acknowledgeAnnouncement, fetchAnnouncement } from '../../api/endpoints/announcements';
import { Button } from '../../components/common/Button';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AnnouncementsStackParamList } from '../../navigation/AnnouncementsStack';

type Props = NativeStackScreenProps<AnnouncementsStackParamList, 'AnnouncementDetail'>;

/** Rough "in 2 days" / "3 days ago" phrasing — mirrors the web's diffForHumans without pulling in a date library. */
function relativeDays(target: Date, now: Date): string {
  const diffMs = target.getTime() - now.getTime();
  const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return 'today';
  if (diffDays > 0) return diffDays === 1 ? 'in 1 day' : `in ${diffDays} days`;
  const past = Math.abs(diffDays);
  return past === 1 ? '1 day ago' : `${past} days ago`;
}

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

  const now = new Date();
  const expiresAt = a.expires_at ? new Date(a.expires_at) : null;
  const isExpired = expiresAt ? expiresAt.getTime() <= now.getTime() : false;
  const expiresSoon = expiresAt
    ? !isExpired && expiresAt.getTime() - now.getTime() <= 3 * 24 * 60 * 60 * 1000
    : false;

  return (
    <Screen padded={!a.banner_image_url}>
      <ScrollView showsVerticalScrollIndicator={false}>
        {a.banner_image_url && <Image source={{ uri: a.banner_image_url }} style={styles.hero} />}

        <View style={a.banner_image_url ? styles.paddedBody : undefined}>
          {(isExpired || expiresSoon) && (
            <View style={{ marginTop: spacing.md, alignSelf: 'flex-start' }}>
              <StatusBadge
                value={isExpired ? 'expired' : 'expiring'}
                label={isExpired ? 'Expired' : `Expiring ${relativeDays(expiresAt!, now)}`}
                color={isExpired ? theme.danger : theme.warning}
                filled
              />
            </View>
          )}

          <View style={styles.metaRow}>
            <Icon name="person-outline" size={14} color={theme.textMuted} />
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              Posted by <Text style={{ fontWeight: '700', color: theme.text }}>{a.creator_name}</Text>
            </Text>
          </View>
          <View style={styles.metaRow}>
            {a.published_at && (
              <>
                <Icon name="calendar-outline" size={14} color={theme.textMuted} />
                <Text style={[typography.caption, { color: theme.textMuted }]}>
                  {new Date(a.published_at).toLocaleDateString()}
                </Text>
              </>
            )}
            <Icon name="eye" size={14} color={theme.textMuted} style={{ marginLeft: a.published_at ? spacing.sm : 0 }} />
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {a.views_count} view{a.views_count === 1 ? '' : 's'}
            </Text>
          </View>

          <Text style={[typography.title, { color: theme.text, marginTop: spacing.sm }]}>{a.title}</Text>
          <Text style={[typography.body, { color: theme.text, marginTop: spacing.md, lineHeight: 22 }]}>
            {a.content}
          </Text>

          {a.requires_acknowledgment && (
            <View style={[styles.card, { backgroundColor: theme.surfaceAlt }]}>
              {a.is_acknowledged ? (
                <>
                  <StatusBadge value="acknowledged" label="Acknowledged" color={theme.success} filled />
                  {a.acknowledged_at && (
                    <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.sm }]}>
                      Confirmed on {new Date(a.acknowledged_at).toLocaleString()}
                    </Text>
                  )}
                </>
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
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.lg },
  hero: { width: '100%', height: 200 },
  paddedBody: { paddingHorizontal: 24 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: spacing.sm },
});
