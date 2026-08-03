import { apiClient } from '../client';
import type { PayslipIndexResponse, PayslipInfo } from '../types';

export async function fetchPayslips(year?: number, status?: string): Promise<PayslipIndexResponse> {
  const { data } = await apiClient.get<PayslipIndexResponse>('/payslips', {
    params: { ...(year ? { year } : {}), ...(status ? { status } : {}) },
  });
  return data;
}

export async function fetchPayslip(id: number): Promise<{ payslip: PayslipInfo }> {
  const { data } = await apiClient.get(`/payslips/${id}`);
  return data;
}

export function payslipPdfUrl(id: number): string {
  return `/payslips/${id}/pdf`;
}
