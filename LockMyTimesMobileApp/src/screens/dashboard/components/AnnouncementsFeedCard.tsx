import { Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { UseQueryResult } from '@tanstack/react-query';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import { SkeletonList } from '../../../components/common/SkeletonBlock';
import { entranceStagger } from '../../../theme/motion';
import { radii, spacing, typography } from '../../../theme/tokens';
import { useTheme } from '../../../theme/useTheme';
import type { MainTabsParamList } from '../../../navigation/MainTabs';
import type { AnnouncementInfo, AnnouncementsIndexResponse } from '../../../api/types';

const PRIORITY_COLOR: Record<string, 'textMuted' | 'primary' | 'warning' | 'danger'> = {
  low: 'textMuted',
  normal: 'primary',
  high: 'warning',
  urgent: 'danger',
};

export function AnnouncementsFeedCard({
  announcementsQuery,
  navigation,
}: {
  announcementsQuery: UseQueryResult<AnnouncementsIndexResponse>;
  navigation: BottomTabNavigationProp<MainTabsParamList, 'Dashboard'>;
}) {
  const theme = useTheme();
  const items = announcementsQuery.data?.announcements.slice(0, 3) ?? [];

  function renderItem(item: AnnouncementInfo, index: number) {
    return (
      <MotiView key={item.id} {...entranceStagger(index)}>
        <Pressable
          onPress={() =>
            navigation.navigate('More', {
              screen: 'Announcements',
              params: { screen: 'AnnouncementDetail', params: { id: item.id } },
            })
          }
          style={[
            styles.card,
            { backgroundColor: item.is_read ? theme.surface : theme.primaryMuted, borderColor: theme.border },
          ]}
        >
          <View style={styles.headerRow}>
            <Text
              style={[typography.body, { color: theme.text, fontWeight: item.is_read ? '400' : '700', flex: 1 }]}
              numberOfLines={1}
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
          {item.needs_action && (
            <Text style={[typography.caption, { color: theme.warning, marginTop: 4, fontWeight: '700' }]}>
              Acknowledgment required
            </Text>
          )}
        </Pressable>
      </MotiView>
    );
  }

  return (
    <View>
      <View style={styles.titleRow}>
        <Text style={[typography.subheading, { color: theme.text }]}>Announcements</Text>
        <Pressable onPress={() => navigation.navigate('More', { screen: 'Announcements', params: { screen: 'AnnouncementList' } })}>
          <Text style={[typography.caption, { color: theme.primary, fontWeight: '600' }]}>See all</Text>
        </Pressable>
      </View>

      {announcementsQuery.isLoading ? (
        <SkeletonList rows={3} rowHeight={64} />
      ) : items.length === 0 ? (
        <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.border }]}>
          <Text style={[typography.caption, { color: theme.textMuted }]}>No announcements right now.</Text>
        </View>
      ) : (
        items.map((item, index) => renderItem(item, index))
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  titleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.sm },
  headerRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm },
  card: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md, marginTop: spacing.sm },
});
