import { apiClient } from '../client';
import type { NotificationsIndexResponse } from '../types';

export async function fetchNotifications(): Promise<NotificationsIndexResponse> {
  const { data } = await apiClient.get<NotificationsIndexResponse>('/notifications');
  return data;
}

export async function markNotificationRead(id: string): Promise<{ success: true }> {
  const { data } = await apiClient.patch(`/notifications/${id}/read`);
  return data;
}

export async function markAllNotificationsRead(): Promise<{ success: true }> {
  const { data } = await apiClient.post('/notifications/read-all');
  return data;
}

export async function registerPushToken(pushToken: string): Promise<{ success: true }> {
  const { data } = await apiClient.post('/notifications/push-token', { push_token: pushToken });
  return data;
}
