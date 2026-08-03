import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchPoll, votePoll } from '../../api/endpoints/announcements';
import { Button } from '../../components/common/Button';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AnnouncementsStackParamList } from '../../navigation/AnnouncementsStack';

type Props = NativeStackScreenProps<AnnouncementsStackParamList, 'Poll'>;

const TYPE_LABEL: Record<string, string> = {
  single_choice: 'Pick one',
  multiple_choice: 'Pick multiple',
  rating: 'Rating',
};

/** Rough "in 2 days" / "3 days ago" phrasing — mirrors the web's diffForHumans without pulling in a date library. */
function relativeDays(target: Date, now: Date): string {
  const diffMs = target.getTime() - now.getTime();
  const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return 'today';
  if (diffDays > 0) return diffDays === 1 ? 'in 1 day' : `in ${diffDays} days`;
  const past = Math.abs(diffDays);
  return past === 1 ? '1 day ago' : `${past} days ago`;
}

export function PollScreen({ route }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const { id } = route.params;
  const [selected, setSelected] = useState<number[]>([]);

  const { data, isLoading } = useQuery({ queryKey: ['polls', id], queryFn: () => fetchPoll(id) });

  const voteMutation = useMutation({
    mutationFn: () => votePoll(id, selected),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['polls', id] }),
  });

  if (isLoading || !data) {
    return (
      <Screen>
        <DetailSkeleton />
      </Screen>
    );
  }

  const { poll, results, show_results, total_votes } = data;
  const myChoices = poll.my_choices ?? [];
  const now = new Date();
  const endsAt = poll.ends_at ? new Date(poll.ends_at) : null;
  const isEnded = endsAt ? endsAt.getTime() <= now.getTime() : false;
  const maxVotes = results.length > 0 ? Math.max(...results.map((r) => r.votes)) : 0;

  function toggleOption(index: number) {
    if (poll.type === 'single_choice') {
      setSelected([index]);
    } else {
      setSelected((prev) => (prev.includes(index) ? prev.filter((i) => i !== index) : [...prev, index]));
    }
  }

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <View style={styles.metaRow}>
          <Text style={[typography.caption, { color: theme.primary, fontWeight: '700', textTransform: 'uppercase' }]}>
            {TYPE_LABEL[poll.type] ?? poll.type.replace(/_/g, ' ')}
          </Text>
          {poll.is_anonymous && (
            <StatusBadge value="anonymous" label="Anonymous" color={theme.accentBlue} filled />
          )}
        </View>

        <Text style={[typography.title, { color: theme.text, marginTop: spacing.sm }]}>{poll.question}</Text>
        {poll.description && (
          <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs }]}>{poll.description}</Text>
        )}

        <View style={styles.metaRow}>
          <Icon name="person-outline" size={13} color={theme.textMuted} />
          <Text style={[typography.caption, { color: theme.textMuted }]}>Posted by {poll.creator_name}</Text>
          {endsAt && (
            <>
              <Icon name="time" size={13} color={theme.textMuted} style={{ marginLeft: spacing.sm }} />
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                {isEnded ? 'Ended' : 'Ends'} {relativeDays(endsAt, now)}
              </Text>
            </>
          )}
        </View>

        <View style={{ marginTop: spacing.lg }}>
          {show_results
            ? results.map((r) => {
                const isMine = myChoices.includes(r.index);
                const isLeading = total_votes > 0 && r.votes === maxVotes && maxVotes > 0;
                return (
                  <View key={r.index} style={styles.resultRow}>
                    <View style={[styles.resultTrack, { backgroundColor: theme.surfaceAlt }]}>
                      <MotiView
                        from={{ width: '0%' }}
                        animate={{ width: `${r.percent}%` }}
                        transition={{ type: 'timing', duration: 500 }}
                        style={[styles.resultFill, { backgroundColor: isMine ? theme.primary : theme.textMuted }]}
                      />
                    </View>
                    <View style={styles.resultLabelRow}>
                      <View style={styles.resultLabelLeft}>
                        {isMine && <Icon name="checkmark" size={15} color={theme.primary} />}
                        <Text style={[typography.body, { color: theme.text, fontWeight: isMine ? '700' : '400' }]}>
                          {r.option}
                        </Text>
                        {isLeading && (
                          <View style={{ marginLeft: 2 }}>
                            <StatusBadge value="leading" label="Leading" color={theme.accentBlue} filled />
                          </View>
                        )}
                      </View>
                      <Text style={[typography.caption, { color: theme.textMuted }]}>
                        {r.votes} ({r.percent}%)
                      </Text>
                    </View>
                  </View>
                );
              })
            : poll.options.map((option, index) => (
                <Pressable
                  key={index}
                  onPress={() => toggleOption(index)}
                  style={[
                    styles.optionRow,
                    {
                      borderColor: selected.includes(index) ? theme.primary : theme.border,
                      backgroundColor: selected.includes(index) ? theme.primaryMuted : theme.surface,
                    },
                  ]}
                >
                  <Text style={[typography.body, { color: theme.text }]}>{option}</Text>
                </Pressable>
              ))}
        </View>

        {show_results && (
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.sm }]}>
            {total_votes} total vote(s)
          </Text>
        )}

        {!show_results && (
          <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
            <Button
              title="Submit vote"
              onPress={() => voteMutation.mutate()}
              loading={voteMutation.isPending}
              disabled={selected.length === 0}
            />
          </View>
        )}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  optionRow: { borderWidth: 1, borderRadius: radii.md, padding: spacing.md, marginBottom: spacing.sm },
  resultRow: { marginBottom: spacing.md },
  resultTrack: { height: 10, borderRadius: 5, overflow: 'hidden' },
  resultFill: { height: 10, borderRadius: 5 },
  resultLabelRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.xs },
  resultLabelLeft: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, flexShrink: 1 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: spacing.sm, flexWrap: 'wrap' },
});
