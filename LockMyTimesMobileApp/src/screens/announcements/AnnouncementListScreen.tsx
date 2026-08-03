import { useQuery } from '@tanstack/react-query';
import { FlatList, Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchAnnouncements } from '../../api/endpoints/announcements';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AnnouncementsStackParamList } from '../../navigation/AnnouncementsStack';
import type { AnnouncementInfo, PollInfo } from '../../api/types';

type Props = NativeStackScreenProps<AnnouncementsStackParamList, 'AnnouncementList'>;

const PRIORITY_COLOR: Record<string, 'textMuted' | 'primary' | 'warning' | 'danger'> = {
  low: 'textMuted',
  normal: 'primary',
  high: 'warning',
  urgent: 'danger',
};

export function AnnouncementListScreen({ navigation }: Props) {
  const theme = useTheme();

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['announcements', 'index'],
    queryFn: () => fetchAnnouncements(),
  });

  function renderAnnouncement({ item, index }: { item: AnnouncementInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => navigation.navigate('AnnouncementDetail', { id: item.id })}
          style={[
            styles.card,
            { backgroundColor: item.is_read ? theme.surface : theme.primaryMuted, borderColor: theme.border },
          ]}
        >
          <View style={styles.rowWithThumb}>
            {item.banner_image_url && (
              <Image source={{ uri: item.banner_image_url }} style={styles.thumb} />
            )}
            <View style={{ flex: 1 }}>
              <View style={styles.headerRow}>
                <Text
                  style={[typography.body, { color: theme.text, fontWeight: item.is_read ? '400' : '700', flex: 1 }]}
                >
                  {item.title}
                </Text>
                <Text style={[typography.caption, { color: theme[PRIORITY_COLOR[item.priority]], fontWeight: '700' }]}>
                  {item.priority.toUpperCase()}
                </Text>
              </View>
              <Text style={[typography.caption, { color: theme.textMuted }]} numberOfLines={2}>
                {item.content}
              </Text>
              <View style={styles.metaRow}>
                <Icon name="eye" size={13} color={theme.textMuted} />
                <Text style={[typography.caption, { color: theme.textMuted }]}>
                  {item.views_count} view{item.views_count === 1 ? '' : 's'}
                </Text>
              </View>
              {item.needs_action && (
                <Text style={[typography.caption, { color: theme.warning, marginTop: 4, fontWeight: '700' }]}>
                  Acknowledgment required
                </Text>
              )}
            </View>
          </View>
        </Pressable>
      </MotiView>
    );
  }

  function renderPoll(poll: PollInfo) {
    return (
      <Pressable
        key={poll.id}
        onPress={() => navigation.navigate('Poll', { id: poll.id })}
        style={[styles.pollChip, { backgroundColor: theme.primary }]}
      >
        <Text style={{ color: theme.onPrimary, fontWeight: '600' }} numberOfLines={1}>
          {poll.question}
        </Text>
        <View style={styles.pollMetaRow}>
          <Text style={{ color: theme.onPrimary, fontSize: 11, opacity: 0.85 }}>
            {poll.options.length} option{poll.options.length === 1 ? '' : 's'}
          </Text>
          {poll.is_anonymous && (
            <View style={[styles.anonTag, { backgroundColor: 'rgba(255,255,255,0.25)' }]}>
              <Icon name="eye-off" size={10} color={theme.onPrimary} />
              <Text style={{ color: theme.onPrimary, fontSize: 9, fontWeight: '700' }}>ANON</Text>
            </View>
          )}
        </View>
        {poll.has_voted && <Text style={{ color: theme.onPrimary, fontSize: 11, opacity: 0.85 }}>Voted</Text>}
      </Pressable>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroRow}>
          <Text style={[typography.title, { color: '#FFFFFF' }]}>Announcements</Text>
          {(data?.counters.unread ?? 0) > 0 && (
            <StatusBadge value={String(data!.counters.unread)} label={`${data!.counters.unread} unread`} color="#FFFFFF" uppercase={false} filled />
          )}
        </View>
      </HeroHeader>

      <View style={styles.padded}>
        {(data?.active_polls.length ?? 0) > 0 && (
          <FlatList
            horizontal
            showsHorizontalScrollIndicator={false}
            data={data!.active_polls}
            keyExtractor={(p) => String(p.id)}
            style={{ marginTop: spacing.md }}
            contentContainerStyle={{ gap: spacing.sm }}
            renderItem={({ item }) => renderPoll(item)}
          />
        )}
      </View>

      <FlatList
        data={data?.announcements ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderAnnouncement}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            No announcements yet.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  headerRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm },
  rowWithThumb: { flexDirection: 'row', gap: spacing.sm },
  thumb: { width: 48, height: 48, borderRadius: radii.sm },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
  card: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md, marginTop: spacing.sm },
  pollChip: { borderRadius: radii.md, padding: spacing.sm + 2, width: 180, gap: 4 },
  pollMetaRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  anonTag: { flexDirection: 'row', alignItems: 'center', gap: 2, paddingHorizontal: 5, paddingVertical: 1, borderRadius: radii.sm },
});
