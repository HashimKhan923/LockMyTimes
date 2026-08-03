import { Icon } from '../../components/common/Icon';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { FlatList, Linking, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { MotiView } from 'moti';
import {
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../api/endpoints/notifications';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { NotificationInfo } from '../../api/types';

export function NotificationsScreen() {
  const theme = useTheme();
  const queryClient = useQueryClient();

  const { data, isRefetching, refetch } = useQuery({
    queryKey: ['notifications'],
    queryFn: fetchNotifications,
    refetchInterval: 60_000,
  });

  const readMutation = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  const readAllMutation = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  function handlePress(item: NotificationInfo) {
    if (!item.is_read) readMutation.mutate(item.id);
    if (item.action_url) Linking.openURL(item.action_url);
  }

  function renderItem({ item, index }: { item: NotificationInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <Pressable
          onPress={() => handlePress(item)}
          style={[
            styles.row,
            { backgroundColor: item.is_read ? theme.surface : theme.primaryMuted },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <Icon
            name={(item.icon as string) ?? 'notifications-outline'}
            size={20}
            color={item.color ?? theme.primary}
          />
          <View style={{ flex: 1, marginLeft: spacing.sm }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: item.is_read ? '400' : '700' }]}>
              {item.title}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {new Date(item.created_at).toLocaleString()}
            </Text>
          </View>
          {!item.is_read && <View style={[styles.dot, { backgroundColor: theme.primary }]} />}
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.headerRow}>
          <View style={styles.titleRow}>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Notifications</Text>
            {(data?.unread_count ?? 0) > 0 && (
              <StatusBadge value={String(data!.unread_count)} color="#FFFFFF" uppercase={false} filled />
            )}
          </View>
          {(data?.unread_count ?? 0) > 0 && (
            <Pressable onPress={() => readAllMutation.mutate()}>
              <Text style={[typography.caption, { color: '#FFFFFF', fontWeight: '700' }]}>Mark all read</Text>
            </Pressable>
          )}
        </View>
      </HeroHeader>

      <FlatList
        data={data?.notifications ?? []}
        keyExtractor={(item) => item.id}
        renderItem={renderItem}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
            You're all caught up.
          </Text>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  dot: { width: 8, height: 8, borderRadius: 4, marginLeft: spacing.sm },
});
