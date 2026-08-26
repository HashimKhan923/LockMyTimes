import { apiClient } from '../client';
import type { CorrectionIndexResponse, CorrectionRequestInfo } from '../types';

export interface SubmitCorrectionPayload {
  work_date: string;
  clock_in?: string;
  clock_out?: string;
  reason: string;
}

export async function fetchCorrections(): Promise<CorrectionIndexResponse> {
  const { data } = await apiClient.get<CorrectionIndexResponse>('/attendance-corrections');
  return data;
}

export async function submitCorrection(
  payload: SubmitCorrectionPayload
): Promise<{ message: string; request: CorrectionRequestInfo }> {
  const { data } = await apiClient.post('/attendance-corrections', payload);
  return data;
}

export async function cancelCorrection(id: number): Promise<{ success: true }> {
  const { data } = await apiClient.post(`/attendance-corrections/${id}/cancel`);
  return data;
}
