import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { DetailSkeleton } from '../../components/common/SkeletonBlock';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchPoll, votePoll } from '../../api/endpoints/announcements';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AnnouncementsStackParamList } from '../../navigation/AnnouncementsStack';

type Props = NativeStackScreenProps<AnnouncementsStackParamList, 'Poll'>;

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
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>{poll.question}</Text>
        {poll.description && (
          <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs }]}>{poll.description}</Text>
        )}

        <View style={{ marginTop: spacing.lg }}>
          {show_results
            ? results.map((r) => (
                <View key={r.index} style={styles.resultRow}>
                  <View style={[styles.resultTrack, { backgroundColor: theme.surfaceAlt }]}>
                    <MotiView
                      from={{ width: '0%' }}
                      animate={{ width: `${r.percent}%` }}
                      transition={{ type: 'timing', duration: 500 }}
                      style={[styles.resultFill, { backgroundColor: theme.primary }]}
                    />
                  </View>
                  <View style={styles.resultLabelRow}>
                    <Text style={[typography.body, { color: theme.text }]}>{r.option}</Text>
                    <Text style={[typography.caption, { color: theme.textMuted }]}>
                      {r.votes} ({r.percent}%)
                    </Text>
                  </View>
                </View>
              ))
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
  resultLabelRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: spacing.xs },
});
