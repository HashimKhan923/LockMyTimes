import { apiClient } from '../client';
import type { AnnouncementsIndexResponse, PollResultsResponse } from '../types';

export async function fetchAnnouncements(filter = 'all'): Promise<AnnouncementsIndexResponse> {
  const { data } = await apiClient.get<AnnouncementsIndexResponse>('/announcements', { params: { filter } });
  return data;
}

export async function fetchAnnouncement(id: number): Promise<{ announcement: AnnouncementsIndexResponse['announcements'][number] }> {
  const { data } = await apiClient.get(`/announcements/${id}`);
  return data;
}

export async function acknowledgeAnnouncement(id: number): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/announcements/${id}/acknowledge`);
  return data;
}

export async function fetchPoll(id: number): Promise<PollResultsResponse> {
  const { data } = await apiClient.get<PollResultsResponse>(`/announcements/polls/${id}`);
  return data;
}

export async function votePoll(id: number, selectedOptions: number[]): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/announcements/polls/${id}/vote`, { selected_options: selectedOptions });
  return data;
}
