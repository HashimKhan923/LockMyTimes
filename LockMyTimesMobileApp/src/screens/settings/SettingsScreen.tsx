import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
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
import { Screen } from '../../components/common/Screen';
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

export function SettingsScreen() {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const user = useAuthStore((s) => s.user);
  const updateUser = useAuthStore((s) => s.updateUser);
  const logout = useAuthStore((s) => s.logout);

  const { data: profileData } = useQuery({ queryKey: ['profile'], queryFn: fetchProfile });

  const themeMutation = useMutation({
    mutationFn: (theme: 'light' | 'dark' | 'system') =>
      updatePreferences({
        locale: user?.locale ?? 'en',
        timezone: user?.timezone ?? 'UTC',
        date_format: (user?.date_format as 'DMY' | 'MDY' | 'YMD') ?? 'DMY',
        time_format: (user?.time_format as '12' | '24') ?? '12',
        theme,
      }),
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

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.padded}>
        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Appearance</Text>
        <View style={styles.themeRow}>
          {(['light', 'dark', 'system'] as const).map((t) => (
            <Pressable
              key={t}
              onPress={() => themeMutation.mutate(t)}
              style={[
                styles.themeChip,
                { backgroundColor: user.theme === t ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
              ]}
            >
              <Text style={{ color: user.theme === t ? theme.onPrimary : theme.text, fontWeight: '600', textTransform: 'capitalize' }}>
                {t}
              </Text>
            </Pressable>
          ))}
        </View>

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Notifications</Text>
        <View style={[styles.card, { backgroundColor: theme.surface }, Platform.OS === "ios" ? elevatedShadow.ios : elevatedShadow.android]}>
          {Object.entries(user.notification_preferences).map(([key, pref]) => (
            <View key={key} style={styles.notifGroup}>
              <Text style={[typography.caption, { color: theme.textMuted }]}>{NOTIF_LABELS[key] ?? key}</Text>
              <ToggleRow label="In-app" value={pref.in_app} onValueChange={(v) => toggleNotif(key, 'in_app', v)} />
              <ToggleRow label="Email" value={pref.email} onValueChange={(v) => toggleNotif(key, 'email', v)} />
            </View>
          ))}
        </View>

        {profileData && (
          <>
            <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Privacy</Text>
            <View style={[styles.card, { backgroundColor: theme.surface }, Platform.OS === "ios" ? elevatedShadow.ios : elevatedShadow.android]}>
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

        <Text style={[typography.subheading, { color: theme.text, marginTop: spacing.lg }]}>Security</Text>
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
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingBottom: spacing.xl },
  themeRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm },
  themeChip: { flex: 1, alignItems: 'center', paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  card: { borderRadius: radii.lg, padding: spacing.md, marginTop: spacing.sm },
  notifGroup: { marginBottom: spacing.sm },
});
