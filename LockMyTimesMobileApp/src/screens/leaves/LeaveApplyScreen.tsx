import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import DateTimePicker from '@react-native-community/datetimepicker';
import * as DocumentPicker from 'expo-document-picker';
import { useEffect, useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { applyLeave, calculateLeave } from '../../api/endpoints/leaves';
import { fetchLeaves } from '../../api/endpoints/leaves';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatNumber } from '../../components/common/StatNumber';
import { TextField } from '../../components/common/TextField';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useToastStore } from '../../stores/toastStore';
import type { LeavesStackParamList } from '../../navigation/LeavesStack';

type Props = NativeStackScreenProps<LeavesStackParamList, 'LeaveApply'>;

function toDateString(d: Date) {
  return d.toISOString().slice(0, 10);
}

export function LeaveApplyScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const showToast = useToastStore((s) => s.show);

  const { data } = useQuery({ queryKey: ['leaves', 'index'], queryFn: () => fetchLeaves() });

  const [typeId, setTypeId] = useState<number | null>(null);
  const [startDate, setStartDate] = useState(new Date());
  const [endDate, setEndDate] = useState(new Date());
  const [showPicker, setShowPicker] = useState<'start' | 'end' | null>(null);
  const [reason, setReason] = useState('');
  const [contact, setContact] = useState('');
  const [attachment, setAttachment] = useState<DocumentPicker.DocumentPickerAsset | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const selectedType = data?.leave_types.find((t) => t.id === typeId) ?? null;

  const calcQuery = useQuery({
    queryKey: ['leaves', 'calculate', toDateString(startDate), toDateString(endDate), typeId],
    queryFn: () =>
      calculateLeave({
        start_date: toDateString(startDate),
        end_date: toDateString(endDate),
        leave_type_id: typeId ?? undefined,
      }),
    enabled: !!typeId,
  });

  useEffect(() => {
    if (endDate < startDate) setEndDate(startDate);
  }, [startDate, endDate]);

  const submitMutation = useMutation({
    mutationFn: () =>
      applyLeave({
        leave_type_id: typeId!,
        start_date: toDateString(startDate),
        end_date: toDateString(endDate),
        reason,
        contact_during_leave: contact || undefined,
        attachment: attachment
          ? { uri: attachment.uri, name: attachment.name, mimeType: attachment.mimeType ?? null }
          : undefined,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['leaves'] });
      showToast('Leave request submitted', 'success');
      navigation.goBack();
    },
    onError: (err) => {
      const msg = extractErrorMessage(err);
      setSubmitError(msg);
      showToast(msg, 'error');
    },
  });

  async function pickAttachment() {
    const result = await DocumentPicker.getDocumentAsync({
      type: ['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    });
    if (!result.canceled && result.assets?.[0]) {
      setAttachment(result.assets[0]);
    }
  }

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Apply for leave</Text>

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Leave type</Text>
        <View style={styles.typeRow}>
          {(data?.leave_types ?? []).map((t) => (
            <Pressable
              key={t.id}
              onPress={() => setTypeId(t.id)}
              style={[
                styles.typeChip,
                {
                  backgroundColor: typeId === t.id ? t.color : theme.surface,
                  borderColor: typeId === t.id ? t.color : theme.border,
                },
              ]}
            >
              <Text style={{ color: typeId === t.id ? '#fff' : theme.text, fontWeight: '600' }}>{t.name}</Text>
            </Pressable>
          ))}
        </View>

        <View style={styles.dateRow}>
          <Pressable
            onPress={() => setShowPicker('start')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>Start date</Text>
            <Text style={[typography.body, { color: theme.text }]}>{toDateString(startDate)}</Text>
          </Pressable>
          <Pressable
            onPress={() => setShowPicker('end')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>End date</Text>
            <Text style={[typography.body, { color: theme.text }]}>{toDateString(endDate)}</Text>
          </Pressable>
        </View>

        {showPicker && (
          <DateTimePicker
            value={showPicker === 'start' ? startDate : endDate}
            mode="date"
            display={Platform.OS === 'ios' ? 'inline' : 'default'}
            minimumDate={showPicker === 'end' ? startDate : undefined}
            onChange={(_, selected) => {
              setShowPicker(Platform.OS === 'ios' ? showPicker : null);
              if (selected) {
                if (showPicker === 'start') setStartDate(selected);
                else setEndDate(selected);
              }
            }}
          />
        )}

        {typeId && calcQuery.data && (
          <MotiView
            from={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            style={[styles.calcCard, { backgroundColor: theme.primaryMuted }]}
          >
            <View style={styles.calcRow}>
              <StatNumber value={calcQuery.data.total} label="Days requested" color={theme.primary} />
              {calcQuery.data.remaining !== null && (
                <StatNumber value={calcQuery.data.remaining} label="Remaining after" color={theme.primary} />
              )}
            </View>
            {calcQuery.data.warnings.map((w, i) => (
              <Text key={i} style={[typography.caption, { color: theme.danger, marginTop: 4 }]}>
                {w}
              </Text>
            ))}
          </MotiView>
        )}

        <TextField
          label="Reason"
          placeholder="Briefly explain your leave"
          multiline
          numberOfLines={3}
          value={reason}
          onChangeText={setReason}
        />
        <TextField
          label="Contact during leave (optional)"
          value={contact}
          onChangeText={setContact}
        />

        {selectedType?.requires_documentation && (
          <Pressable
            onPress={pickAttachment}
            style={[styles.attachBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.body, { color: attachment ? theme.text : theme.textMuted }]}>
              {attachment ? attachment.name : 'Attach supporting document (required)'}
            </Text>
          </Pressable>
        )}

        {submitError && (
          <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{submitError}</Text>
        )}

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button
            title="Submit request"
            onPress={() => submitMutation.mutate()}
            loading={submitMutation.isPending}
            disabled={!typeId || !reason.trim()}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  typeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.sm },
  typeChip: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
  dateRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg },
  dateBox: {
    flex: 1,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.sm + 4,
  },
  calcCard: {
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.md,
  },
  calcRow: { flexDirection: 'row', gap: spacing.xl },
  attachBox: {
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.md,
    marginTop: spacing.xs,
  },
});
