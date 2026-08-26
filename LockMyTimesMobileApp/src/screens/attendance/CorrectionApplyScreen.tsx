import { useMutation, useQueryClient } from '@tanstack/react-query';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { submitCorrection } from '../../api/endpoints/attendance-corrections';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useToastStore } from '../../stores/toastStore';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'CorrectionApply'>;

function toDateString(d: Date) {
  return d.toISOString().slice(0, 10);
}

function toTimeString(d: Date) {
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

export function CorrectionApplyScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const showToast = useToastStore((s) => s.show);

  const [workDate, setWorkDate] = useState(new Date());
  const [showDatePicker, setShowDatePicker] = useState(false);

  const [clockIn, setClockIn] = useState<Date | null>(null);
  const [clockOut, setClockOut] = useState<Date | null>(null);
  const [showPicker, setShowPicker] = useState<'in' | 'out' | null>(null);

  const [reason, setReason] = useState('');
  const [submitError, setSubmitError] = useState<string | null>(null);

  const submitMutation = useMutation({
    mutationFn: () =>
      submitCorrection({
        work_date: toDateString(workDate),
        clock_in: clockIn ? toTimeString(clockIn) : undefined,
        clock_out: clockOut ? toTimeString(clockOut) : undefined,
        reason,
      }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['attendance-corrections'] });
      showToast(data.message, 'success');
      navigation.goBack();
    },
    onError: (err) => {
      const msg = extractErrorMessage(err);
      setSubmitError(msg);
      showToast(msg, 'error');
    },
  });

  const canSubmit = (clockIn || clockOut) && reason.trim().length >= 5;

  return (
    <Screen>
      <ScrollView showsVerticalScrollIndicator={false}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Request a correction</Text>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
          Forgot to clock in or out? Submit the correct time for review.
        </Text>

        <Pressable
          onPress={() => setShowDatePicker(true)}
          style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface, marginTop: spacing.lg }]}
        >
          <Text style={[typography.caption, { color: theme.textMuted }]}>Date</Text>
          <Text style={[typography.body, { color: theme.text }]}>{toDateString(workDate)}</Text>
        </Pressable>
        {showDatePicker && (
          <DateTimePicker
            value={workDate}
            mode="date"
            display={Platform.OS === 'ios' ? 'inline' : 'default'}
            maximumDate={new Date()}
            onChange={(_, selected) => {
              setShowDatePicker(Platform.OS === 'ios');
              if (selected) setWorkDate(selected);
            }}
          />
        )}

        <View style={styles.dateRow}>
          <Pressable
            onPress={() => setShowPicker('in')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>Correct clock in</Text>
            <Text style={[typography.body, { color: theme.text }]}>
              {clockIn ? toTimeString(clockIn) : '—'}
            </Text>
          </Pressable>
          <Pressable
            onPress={() => setShowPicker('out')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>Correct clock out</Text>
            <Text style={[typography.body, { color: theme.text }]}>
              {clockOut ? toTimeString(clockOut) : '—'}
            </Text>
          </Pressable>
        </View>
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
          Fill in whichever one you missed — you don't need both.
        </Text>

        {showPicker && (
          <DateTimePicker
            value={(showPicker === 'in' ? clockIn : clockOut) ?? new Date()}
            mode="time"
            display={Platform.OS === 'ios' ? 'spinner' : 'default'}
            onChange={(_, selected) => {
              setShowPicker(Platform.OS === 'ios' ? showPicker : null);
              if (selected) {
                if (showPicker === 'in') setClockIn(selected);
                else setClockOut(selected);
              }
            }}
          />
        )}

        <TextField
          label="Reason"
          placeholder="Explain briefly why you're requesting this correction"
          multiline
          numberOfLines={3}
          value={reason}
          onChangeText={setReason}
        />

        {submitError && (
          <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>{submitError}</Text>
        )}

        <View style={{ marginTop: spacing.lg, marginBottom: spacing.xl }}>
          <Button
            title="Submit request"
            onPress={() => submitMutation.mutate()}
            loading={submitMutation.isPending}
            disabled={!canSubmit}
          />
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  dateRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg },
  dateBox: {
    flex: 1,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.sm + 4,
  },
});
