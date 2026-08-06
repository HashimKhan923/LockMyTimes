import { apiClient } from '../client';
import type {
  AttendanceDayResponse,
  AttendanceHistoryResponse,
  AttendanceIndexResponse,
  AttendanceRecord,
  AttendanceStatusResponse,
} from '../types';

export async function fetchAttendanceIndex(month?: string): Promise<AttendanceIndexResponse> {
  const { data } = await apiClient.get<AttendanceIndexResponse>('/attendance', {
    params: month ? { month } : undefined,
  });
  return data;
}

export async function fetchAttendanceStatus(): Promise<AttendanceStatusResponse> {
  const { data } = await apiClient.get<AttendanceStatusResponse>('/attendance/status');
  return data;
}

export async function fetchAttendanceDay(date: string): Promise<AttendanceDayResponse> {
  const { data } = await apiClient.get<AttendanceDayResponse>(`/attendance/${date}`);
  return data;
}

export async function fetchAttendanceHistory(from?: string, to?: string): Promise<AttendanceHistoryResponse> {
  const { data } = await apiClient.get<AttendanceHistoryResponse>('/attendance/history', {
    params: { ...(from ? { from } : {}), ...(to ? { to } : {}) },
  });
  return data;
}

export interface ClockInPayload {
  source: 'mobile' | 'qr';
  location_id?: number;
  qr_token?: string;
  lat?: number;
  lng?: number;
  notes?: string;
}

export async function clockIn(
  payload: ClockInPayload
): Promise<{ success: true; message: string; attendance: AttendanceRecord }> {
  const { data } = await apiClient.post('/attendance/clock-in', payload);
  return data;
}

export interface ClockOutPayload {
  source: 'mobile' | 'qr';
  lat?: number;
  lng?: number;
  notes?: string;
}

export async function clockOut(
  payload: ClockOutPayload
): Promise<{ success: true; message: string; attendance: AttendanceRecord }> {
  const { data } = await apiClient.post('/attendance/clock-out', payload);
  return data;
}

export async function startBreak(breakType: string): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post('/attendance/breaks/start', { break_type: breakType });
  return data;
}

export async function endBreak(): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post('/attendance/breaks/end');
  return data;
}
