import { apiClient } from '../client';
import type { ExpenseInfo, ExpensesIndexResponse } from '../types';

export async function fetchExpenses(year?: number): Promise<ExpensesIndexResponse> {
  const { data } = await apiClient.get<ExpensesIndexResponse>('/expenses', {
    params: year ? { year } : undefined,
  });
  return data;
}

export async function fetchExpense(id: number): Promise<{ expense: ExpenseInfo }> {
  const { data } = await apiClient.get(`/expenses/${id}`);
  return data;
}

export interface CreateExpensePayload {
  category_id: number;
  title: string;
  description?: string;
  amount: number;
  expense_date: string;
  merchant?: string;
  is_mileage?: boolean;
  miles?: number;
  action?: 'draft' | 'submit';
  receipt?: { uri: string; name: string; mimeType: string | null };
}

export async function createExpense(
  payload: CreateExpensePayload
): Promise<{ success: true; message: string; expense: ExpenseInfo }> {
  const form = new FormData();
  form.append('category_id', String(payload.category_id));
  form.append('title', payload.title);
  if (payload.description) form.append('description', payload.description);
  form.append('amount', String(payload.amount));
  form.append('expense_date', payload.expense_date);
  if (payload.merchant) form.append('merchant', payload.merchant);
  if (payload.is_mileage) form.append('is_mileage', '1');
  if (payload.miles) form.append('miles', String(payload.miles));
  form.append('action', payload.action ?? 'submit');
  if (payload.receipt) {
    form.append('receipt', {
      uri: payload.receipt.uri,
      name: payload.receipt.name,
      type: payload.receipt.mimeType ?? 'image/jpeg',
    } as unknown as Blob);
  }

  const { data } = await apiClient.post('/expenses', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return data;
}

export async function deleteExpense(id: number): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.delete(`/expenses/${id}`);
  return data;
}
