import { apiClient } from '../client';
import type { NotificationPreference, UserProfile } from '../types';

// timezone is deliberately not part of this payload — it's admin-managed only (set from
// Admin > Employees on the web), never an employee self-service preference.
export interface UpdatePreferencesPayload {
  locale: string;
  date_format: 'DMY' | 'MDY' | 'YMD';
  time_format: '12' | '24';
  theme: 'light' | 'dark' | 'system';
}

export async function updatePreferences(
  payload: UpdatePreferencesPayload
): Promise<{ message: string; user: UserProfile }> {
  const { data } = await apiClient.put('/settings', payload);
  return data;
}

export async function updateNotificationPreferences(
  preferences: Record<string, NotificationPreference>
): Promise<{ message: string; user: UserProfile }> {
  const { data } = await apiClient.put('/settings/notifications', { preferences });
  return data;
}

export async function updatePrivacySettings(
  settings: Record<string, boolean>
): Promise<{ message: string; privacy_settings: Record<string, boolean> }> {
  const { data } = await apiClient.put('/settings/privacy', { settings });
  return data;
}
