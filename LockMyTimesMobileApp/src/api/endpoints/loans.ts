import { apiClient } from '../client';
import type { EmiCalculation, LoanInfo, LoanTypeInfo, LoansIndexResponse, SalaryAdvanceInfo } from '../types';

export async function fetchLoansIndex(): Promise<LoansIndexResponse> {
  const { data } = await apiClient.get<LoansIndexResponse>('/loans');
  return data;
}

export async function fetchLoanTypes(): Promise<{ loan_types: LoanTypeInfo[]; service_months: number; base_salary: number }> {
  const { data } = await apiClient.get('/loans/types');
  return data;
}

export async function calculateEmi(
  principal: number,
  tenureMonths: number,
  interestRate: number
): Promise<EmiCalculation> {
  const { data } = await apiClient.post<EmiCalculation>('/loans/calculate-emi', {
    principal,
    tenure_months: tenureMonths,
    interest_rate: interestRate,
  });
  return data;
}

export interface ApplyLoanPayload {
  loan_type_id: number;
  principal_amount: number;
  tenure_months: number;
  purpose: string;
  first_emi_date: string;
}

export async function applyLoan(
  payload: ApplyLoanPayload
): Promise<{ success: true; message: string; loan: LoanInfo }> {
  const { data } = await apiClient.post('/loans', payload);
  return data;
}

export async function fetchLoan(id: number): Promise<{ loan: LoanInfo }> {
  const { data } = await apiClient.get(`/loans/${id}`);
  return data;
}

export async function cancelLoan(id: number): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/loans/${id}/cancel`);
  return data;
}

export interface ApplyAdvancePayload {
  amount: number;
  reason: string;
  repayment_type: 'one_time' | 'installments';
  installments_count?: number;
}

export async function applyAdvance(
  payload: ApplyAdvancePayload
): Promise<{ success: true; message: string; advance: SalaryAdvanceInfo }> {
  const { data } = await apiClient.post('/loans/advances', payload);
  return data;
}

export async function fetchAdvance(id: number): Promise<{ advance: SalaryAdvanceInfo }> {
  const { data } = await apiClient.get(`/loans/advances/${id}`);
  return data;
}

export async function cancelAdvance(id: number): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/loans/advances/${id}/cancel`);
  return data;
}
