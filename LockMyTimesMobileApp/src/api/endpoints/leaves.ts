import { apiClient } from '../client';
import type { LeaveCalculateResponse, LeaveIndexResponse, LeaveRequestInfo } from '../types';

export async function fetchLeaves(year?: number): Promise<LeaveIndexResponse> {
  const { data } = await apiClient.get<LeaveIndexResponse>('/leaves', {
    params: year ? { year } : undefined,
  });
  return data;
}

export async function fetchLeave(id: number): Promise<{ leave: LeaveRequestInfo }> {
  const { data } = await apiClient.get(`/leaves/${id}`);
  return data;
}

export interface CalculatePayload {
  start_date: string;
  end_date: string;
  day_part?: string;
  leave_type_id?: number;
}

export async function calculateLeave(payload: CalculatePayload): Promise<LeaveCalculateResponse> {
  const { data } = await apiClient.post<LeaveCalculateResponse>('/leaves/calculate', payload);
  return data;
}

export interface ApplyLeavePayload {
  leave_type_id: number;
  start_date: string;
  end_date: string;
  day_part?: string;
  reason: string;
  contact_during_leave?: string;
  attachment?: { uri: string; name: string; mimeType: string | null };
}

export async function applyLeave(
  payload: ApplyLeavePayload
): Promise<{ success: true; message: string; leave: LeaveRequestInfo }> {
  const form = new FormData();
  form.append('leave_type_id', String(payload.leave_type_id));
  form.append('start_date', payload.start_date);
  form.append('end_date', payload.end_date);
  if (payload.day_part) form.append('day_part', payload.day_part);
  form.append('reason', payload.reason);
  if (payload.contact_during_leave) form.append('contact_during_leave', payload.contact_during_leave);
  if (payload.attachment) {
    form.append('attachment', {
      uri: payload.attachment.uri,
      name: payload.attachment.name,
      type: payload.attachment.mimeType ?? 'application/octet-stream',
    } as unknown as Blob);
  }

  const { data } = await apiClient.post('/leaves', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return data;
}

export async function cancelLeave(id: number): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/leaves/${id}/cancel`);
  return data;
}
