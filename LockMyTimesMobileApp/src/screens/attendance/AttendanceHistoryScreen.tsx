import { useQuery } from '@tanstack/react-query';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchAttendanceHistory } from '../../api/endpoints/attendance';
import { Icon } from '../../components/common/Icon';
import { Screen } from '../../components/common/Screen';
import { SkeletonList } from '../../components/common/SkeletonBlock';
import { StatusBadge } from '../../components/common/StatusBadge';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';
import type { AttendanceRecord } from '../../api/types';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'History'>;

const STATUS_LABEL: Record<string, string> = {
  present: 'Present',
  absent: 'Absent',
  half_day: 'Half day',
  on_leave: 'On leave',
  holiday: 'Holiday',
};

function toDateString(d: Date) {
  return d.toISOString().slice(0, 10);
}

function formatDisplayDate(iso: string) {
  return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatTime(iso: string | null) {
  if (!iso) return '--:--';
  return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

export function AttendanceHistoryScreen({ navigation }: Props) {
  const theme = useTheme();

  const [fromDate, setFromDate] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() - 29);
    return d;
  });
  const [toDate, setToDate] = useState(new Date());
  const [showPicker, setShowPicker] = useState<'from' | 'to' | null>(null);

  const from = toDateString(fromDate);
  const to = toDateString(toDate);

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['attendance', 'history', from, to],
    queryFn: () => fetchAttendanceHistory(from, to),
  });

  function renderRecord({ item, index }: { item: AttendanceRecord; index: number }) {
    const dotColor = item.is_late
      ? theme.warning
      : item.status === 'present'
        ? theme.success
        : item.status === 'absent'
          ? theme.danger
          : item.status === 'on_leave' || item.status === 'holiday'
            ? theme.primary
            : theme.border;

    return (
      <MotiView {...entranceStagger(Math.min(index, 10))}>
        <Pressable
          onPress={() => navigation.navigate('DayDetail', { date: item.work_date })}
          style={[
            styles.row,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={[styles.dot, { backgroundColor: dotColor }]} />
          <View style={{ flex: 1 }}>
            <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
              {formatDisplayDate(item.work_date)}
            </Text>
            <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>
              {formatTime(item.clock_in_at)} – {formatTime(item.clock_out_at)}
              {item.is_remote_clockin
                ? ` · Remote${[item.clock_in_city, item.clock_in_country].filter(Boolean).length ? ' (' + [item.clock_in_city, item.clock_in_country].filter(Boolean).join(', ') + ')' : ''}`
                : item.location?.name
                  ? ` · ${item.location.name}`
                  : ''}
            </Text>
          </View>
          <View style={{ alignItems: 'flex-end' }}>
            {item.total_hours > 0 && (
              <Text style={[typography.body, { color: theme.text, fontWeight: '700' }]}>{item.total_hours}h</Text>
            )}
            <StatusBadge
              value={item.status}
              label={item.is_late ? 'Late' : (STATUS_LABEL[item.status] ?? item.status)}
              color={dotColor}
              uppercase={false}
            />
          </View>
        </Pressable>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <View style={styles.headerRow}>
          <Pressable onPress={() => navigation.goBack()} hitSlop={10}>
            <Icon name="arrow-back" size={22} color={theme.text} />
          </Pressable>
          <Text style={[typography.title, { color: theme.text, marginLeft: spacing.sm }]}>Attendance history</Text>
        </View>

        <View style={styles.dateRow}>
          <Pressable
            onPress={() => setShowPicker('from')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>From</Text>
            <Text style={[typography.body, { color: theme.text }]}>{from}</Text>
          </Pressable>
          <Pressable
            onPress={() => setShowPicker('to')}
            style={[styles.dateBox, { borderColor: theme.border, backgroundColor: theme.surface }]}
          >
            <Text style={[typography.caption, { color: theme.textMuted }]}>To</Text>
            <Text style={[typography.body, { color: theme.text }]}>{to}</Text>
          </Pressable>
        </View>

        {showPicker && (
          <DateTimePicker
            value={showPicker === 'from' ? fromDate : toDate}
            mode="date"
            display={Platform.OS === 'ios' ? 'inline' : 'default'}
            maximumDate={showPicker === 'from' ? toDate : new Date()}
            minimumDate={showPicker === 'to' ? fromDate : undefined}
            onChange={(_, selected) => {
              setShowPicker(Platform.OS === 'ios' ? showPicker : null);
              if (selected) {
                if (showPicker === 'from') setFromDate(selected);
                else setToDate(selected);
              }
            }}
          />
        )}

        {data && (
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.sm }]}>
            {data.pagination.total} record{data.pagination.total === 1 ? '' : 's'} found
          </Text>
        )}
      </View>

      {isLoading ? (
        <View style={styles.padded}>
          <SkeletonList rows={5} rowHeight={64} />
        </View>
      ) : (
        <FlatList
          data={data?.records ?? []}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderRecord}
          contentContainerStyle={styles.padded}
          refreshing={isFetching}
          ListEmptyComponent={
            <View style={[styles.emptyCard, { backgroundColor: theme.surface }]}>
              <Text style={[typography.caption, { color: theme.textMuted }]}>
                No attendance records in this date range.
              </Text>
            </View>
          }
        />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  headerRow: { flexDirection: 'row', alignItems: 'center', marginTop: spacing.md },
  dateRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg },
  dateBox: {
    flex: 1,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.md,
    borderRadius: radii.md,
    marginTop: spacing.sm,
    gap: spacing.sm,
  },
  dot: { width: 8, height: 8, borderRadius: 4 },
  emptyCard: { borderRadius: radii.md, padding: spacing.md, marginTop: spacing.lg },
});
