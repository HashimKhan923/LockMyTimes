import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';
import { Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { extractErrorMessage } from '../../api/client';
import { logoutRequest, signOutOtherSessions } from '../../api/endpoints/auth';
import { fetchProfile } from '../../api/endpoints/profile';
import {
  updateNotificationPreferences,
  updatePreferences,
  updatePrivacySettings,
} from '../../api/endpoints/settings';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Icon } from '../../components/common/Icon';
import { IconInfoCard, IconInfoRow } from '../../components/common/IconInfoRow';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { ToggleRow } from '../../components/common/ToggleRow';
import { useAuthStore } from '../../stores/authStore';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { NotificationPreference } from '../../api/types';

const NOTIF_LABELS: Record<string, string> = {
  leave_approvals: 'Leave approvals',
  payslip_available: 'New payslips',
  announcements: 'Announcements',
  expense_updates: 'Expense updates',
  loan_updates: 'Loan updates',
  task_assignments: 'Task assignments',
};

const PRIVACY_LABELS: Record<string, string> = {
  show_in_directory: 'Show me in the directory',
  show_phone: 'Show my phone number',
  show_email: 'Show my email',
  show_department: 'Show my department',
  show_position: 'Show my position',
};

const LOCALES: { code: string; label: string }[] = [
  { code: 'en', label: 'English' },
  { code: 'es', label: 'Español' },
  { code: 'fr', label: 'Français' },
  { code: 'ar', label: 'العربية' },
];

const DATE_FORMATS: { value: 'DMY' | 'MDY' | 'YMD'; label: string }[] = [
  { value: 'DMY', label: 'DD/MM/YYYY' },
  { value: 'MDY', label: 'MM/DD/YYYY' },
  { value: 'YMD', label: 'YYYY-MM-DD' },
];

const TIME_FORMATS: { value: '12' | '24'; label: string }[] = [
  { value: '12', label: '12-hour (2:30 PM)' },
  { value: '24', label: '24-hour (14:30)' },
];

type Tab = 'preferences' | 'notifications' | 'privacy' | 'security';

const TABS: { key: Tab; label: string }[] = [
  { key: 'preferences', label: 'Preferences' },
  { key: 'notifications', label: 'Notifications' },
  { key: 'privacy', label: 'Privacy' },
  { key: 'security', label: 'Security' },
];

export function SettingsScreen() {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const user = useAuthStore((s) => s.user);
  const updateUser = useAuthStore((s) => s.updateUser);
  const logout = useAuthStore((s) => s.logout);
  const [tab, setTab] = useState<Tab>('preferences');

  const { data: profileData } = useQuery({ queryKey: ['profile'], queryFn: fetchProfile });

  const [locale, setLocale] = useState(user?.locale ?? 'en');
  const [timezone, setTimezone] = useState(user?.timezone ?? 'UTC');
  const [dateFormat, setDateFormat] = useState<'DMY' | 'MDY' | 'YMD'>((user?.date_format as 'DMY' | 'MDY' | 'YMD') ?? 'DMY');
  const [timeFormat, setTimeFormat] = useState<'12' | '24'>((user?.time_format as '12' | '24') ?? '12');

  useEffect(() => {
    if (!user) return;
    setLocale(user.locale);
    setTimezone(user.timezone ?? 'UTC');
    setDateFormat((user.date_format as 'DMY' | 'MDY' | 'YMD') ?? 'DMY');
    setTimeFormat((user.time_format as '12' | '24') ?? '12');
  }, [user]);

  const preferencesMutation = useMutation({
    mutationFn: () =>
      updatePreferences({
        locale,
        timezone,
        date_format: dateFormat,
        time_format: timeFormat,
        theme: (user?.theme as 'light' | 'dark' | 'system') ?? 'system',
      }),
    onSuccess: (data) => updateUser(data.user),
  });

  const themeMutation = useMutation({
    mutationFn: (nextTheme: 'light' | 'dark' | 'system') =>
      updatePreferences({ locale, timezone, date_format: dateFormat, time_format: timeFormat, theme: nextTheme }),
    onSuccess: (data) => updateUser(data.user),
  });

  const notifMutation = useMutation({
    mutationFn: (prefs: Record<string, NotificationPreference>) => updateNotificationPreferences(prefs),
    onSuccess: (data) => updateUser(data.user),
  });

  const privacyMutation = useMutation({
    mutationFn: (settings: Record<string, boolean>) => updatePrivacySettings(settings),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['profile'] }),
  });

  function toggleNotif(key: string, channel: 'in_app' | 'email', value: boolean) {
    if (!user) return;
    const next = {
      ...user.notification_preferences,
      [key]: { ...user.notification_preferences[key], [channel]: value },
    };
    notifMutation.mutate(next);
  }

  function togglePrivacy(key: string, value: boolean) {
    if (!profileData) return;
    privacyMutation.mutate({ ...profileData.employee.privacy_settings, [key]: value });
  }

  const [signOutMessage, setSignOutMessage] = useState<string | null>(null);
  const signOutOtherMutation = useMutation({
    mutationFn: signOutOtherSessions,
    onSuccess: (data) => setSignOutMessage(data.message),
  });

  async function handleLogout() {
    try {
      await logoutRequest();
    } finally {
      logout();
    }
  }

  if (!user) return <Screen>{null}</Screen>;

  return (
    <Screen padded={false}>
      <HeroHeader>
        <Text style={[typography.title, { color: '#FFFFFF' }]}>Settings</Text>
      </HeroHeader>

      <View style={styles.padded}>
        <View style={styles.tabRow}>
          {TABS.map(({ key, label }) => (
            <Pressable
              key={key}
              onPress={() => setTab(key)}
              style={[
                styles.tabChip,
                { backgroundColor: tab === key ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
              ]}
            >
              <Text style={{ color: tab === key ? theme.onPrimary : theme.text, fontWeight: '600' }}>{label}</Text>
            </Pressable>
          ))}
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.padded}>
        {tab === 'preferences' && (
          <>
            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Language</Text>
            <View style={styles.chipRow}>
              {LOCALES.map((l) => (
                <Pressable
                  key={l.code}
                  onPress={() => setLocale(l.code)}
                  style={[
                    styles.chip,
                    { backgroundColor: locale === l.code ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
                  ]}
                >
                  <Text style={{ color: locale === l.code ? theme.onPrimary : theme.text, fontWeight: '600' }}>
                    {l.label}
                  </Text>
                </Pressable>
              ))}
            </View>

            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Timezone</Text>
            <TextField
              label="IANA timezone (e.g. America/New_York)"
              value={timezone}
              onChangeText={setTimezone}
              autoCapitalize="none"
            />

            <Text style={[typography.subheading, { color: theme.text }]}>Date format</Text>
            <View style={styles.chipRow}>
              {DATE_FORMATS.map((d) => (
                <Pressable
                  key={d.value}
                  onPress={() => setDateFormat(d.value)}
                  style={[
                    styles.chip,
                    { backgroundColor: dateFormat === d.value ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
                  ]}
                >
                  <Text style={{ color: dateFormat === d.value ? theme.onPrimary : theme.text, fontWeight: '600' }}>
                    {d.label}
                  </Text>
                </Pressable>
              ))}
            </View>

            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Time format</Text>
            <View style={styles.chipRow}>
              {TIME_FORMATS.map((t) => (
                <Pressable
                  key={t.value}
                  onPress={() => setTimeFormat(t.value)}
                  style={[
                    styles.chip,
                    { backgroundColor: timeFormat === t.value ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
                  ]}
                >
                  <Text style={{ color: timeFormat === t.value ? theme.onPrimary : theme.text, fontWeight: '600' }}>
                    {t.label}
                  </Text>
                </Pressable>
              ))}
            </View>

            <View style={{ marginTop: spacing.lg }}>
              <Button
                title="Save preferences"
                onPress={() => preferencesMutation.mutate()}
                loading={preferencesMutation.isPending}
              />
              {preferencesMutation.isError && (
                <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>
                  {extractErrorMessage(preferencesMutation.error)}
                </Text>
              )}
              {preferencesMutation.isSuccess && (
                <Text style={[typography.caption, { color: theme.success, marginTop: spacing.sm }]}>
                  Preferences saved.
                </Text>
              )}
            </View>

            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Appearance</Text>
            <View style={styles.chipRow}>
              {(['light', 'dark', 'system'] as const).map((t) => (
                <Pressable
                  key={t}
                  onPress={() => themeMutation.mutate(t)}
                  style={[
                    styles.chip,
                    { backgroundColor: user.theme === t ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
                  ]}
                >
                  <Text
                    style={{
                      color: user.theme === t ? theme.onPrimary : theme.text,
                      fontWeight: '600',
                      textTransform: 'capitalize',
                    }}
                  >
                    {t}
                  </Text>
                </Pressable>
              ))}
            </View>
          </>
        )}

        {tab === 'notifications' && (
          <>
            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Notifications</Text>
            <View
              style={[
                styles.card,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              {Object.entries(user.notification_preferences).map(([key, pref]) => (
                <View key={key} style={styles.notifGroup}>
                  <Text style={[typography.caption, { color: theme.textMuted }]}>{NOTIF_LABELS[key] ?? key}</Text>
                  <ToggleRow label="In-app" value={pref.in_app} onValueChange={(v) => toggleNotif(key, 'in_app', v)} />
                  <ToggleRow label="Email" value={pref.email} onValueChange={(v) => toggleNotif(key, 'email', v)} />
                </View>
              ))}
            </View>
          </>
        )}

        {tab === 'privacy' && profileData && (
          <>
            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Privacy</Text>
            <View
              style={[
                styles.card,
                { backgroundColor: theme.surface },
                Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
              ]}
            >
              {Object.entries(profileData.employee.privacy_settings).map(([key, value]) => (
                <ToggleRow
                  key={key}
                  label={PRIVACY_LABELS[key] ?? key}
                  value={value}
                  onValueChange={(v) => togglePrivacy(key, v)}
                />
              ))}
            </View>
          </>
        )}

        {tab === 'security' && (
          <>
            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>
              Last login activity
            </Text>
            <IconInfoCard>
              <IconInfoRow
                icon="time"
                label="Last login"
                value={user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'No record available'}
              />
              <IconInfoRow icon="globe" label="IP address" value={user.last_login_ip ?? 'Unknown'} last />
            </IconInfoCard>

            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Sessions</Text>
            <View style={{ marginTop: spacing.sm }}>
              <Button
                title="Sign out other sessions"
                variant="secondary"
                onPress={() => signOutOtherMutation.mutate()}
                loading={signOutOtherMutation.isPending}
              />
              {signOutMessage && (
                <Text style={[typography.caption, { color: theme.success, marginTop: spacing.sm }]}>
                  {signOutMessage}
                </Text>
              )}
            </View>

            <View style={{ marginTop: spacing.md, marginBottom: spacing.xl }}>
              <Button title="Sign out" variant="danger" onPress={handleLogout} />
            </View>
          </>
        )}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingBottom: spacing.xl },
  tabRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md },
  tabChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.sm },
  chip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
  notifGroup: { marginBottom: spacing.sm },
});
