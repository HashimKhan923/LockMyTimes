import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Alert, FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { AppRefreshControl } from '../../components/common/AppRefreshControl';
import { extractErrorMessage } from '../../api/client';
import { cancelCorrection, fetchCorrections } from '../../api/endpoints/attendance-corrections';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useToastStore } from '../../stores/toastStore';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';
import { useResetOnTabBlur } from '../../navigation/useResetOnTabBlur';
import type { CorrectionRequestInfo } from '../../api/types';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'CorrectionList'>;

const STATUS_COLOR: Record<string, 'success' | 'warning' | 'danger' | 'textMuted'> = {
  approved: 'success',
  pending: 'warning',
  rejected: 'danger',
  cancelled: 'textMuted',
};

function formatTime(t: string | null) {
  if (!t) return null;
  const [h, m] = t.split(':').map(Number);
  const d = new Date();
  d.setHours(h, m);
  return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export function CorrectionListScreen({ navigation }: Props) {
  useResetOnTabBlur(navigation);
  const theme = useTheme();
  const queryClient = useQueryClient();
  const showToast = useToastStore((s) => s.show);

  const { data, isLoading, isRefetching, refetch } = useQuery({
    queryKey: ['attendance-corrections'],
    queryFn: () => fetchCorrections(),
  });

  const cancelMutation = useMutation({
    mutationFn: (id: number) => cancelCorrection(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-corrections'] });
      showToast('Correction request cancelled', 'success');
    },
    onError: (err) => showToast(extractErrorMessage(err), 'error'),
  });

  function confirmCancel(item: CorrectionRequestInfo) {
    Alert.alert(
      'Cancel this request?',
      `Correction request ${item.request_number} will be cancelled.`,
      [
        { text: 'Keep it', style: 'cancel' },
        { text: 'Cancel request', style: 'destructive', onPress: () => cancelMutation.mutate(item.id) },
      ]
    );
  }

  function renderRequest({ item, index }: { item: CorrectionRequestInfo; index: number }) {
    const inTime = formatTime(item.proposed_clock_in);
    const outTime = formatTime(item.proposed_clock_out);

    return (
      <MotiView {...entranceStagger(index)}>
        <View
          style={[
            styles.reqCard,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
              {new Date(item.work_date + 'T00:00:00').toLocaleDateString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
              })}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {[inTime && `In ${inTime}`, outTime && `Out ${outTime}`].filter(Boolean).join(' · ')}
            </Text>
            {item.status === 'rejected' && item.rejection_reason && (
              <Text style={[typography.caption, { color: theme.danger, marginTop: 2 }]} numberOfLines={1}>
                {item.rejection_reason}
              </Text>
            )}
          </View>
          <View style={{ alignItems: 'flex-end', gap: spacing.xs }}>
            <StatusBadge value={item.status} color={theme[STATUS_COLOR[item.status]]} filled />
            {item.status === 'pending' && (
              <Pressable onPress={() => confirmCancel(item)}>
                <Text style={[typography.caption, { color: theme.danger, fontWeight: '700' }]}>Cancel</Text>
              </Pressable>
            )}
          </View>
        </View>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroRow}>
          <View style={styles.heroTitleCol}>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>Corrections</Text>
            <Text style={[typography.body, { color: 'rgba(255,255,255,0.9)', marginTop: 2 }]}>
              Fix a missed clock in or out
            </Text>
          </View>
          <Button title="New" variant="accent" icon="add" compact onPress={() => navigation.navigate('CorrectionApply')} />
        </View>
      </HeroHeader>

      <FlatList
        data={data?.requests ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderRequest}
        contentContainerStyle={styles.padded}
        refreshControl={<AppRefreshControl refreshing={isRefetching} onRefresh={refetch} />}
        ListEmptyComponent={
          !isLoading ? (
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.lg }]}>
              No correction requests yet.
            </Text>
          ) : null
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  heroRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  heroTitleCol: { flex: 1, marginRight: spacing.sm },
  reqCard: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
});
