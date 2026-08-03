import { apiClient } from '../client';
import type { LeaveRequestInfo, TeamIndexResponse, TeamMemberDetailResponse } from '../types';
import type { ExpenseInfo } from '../types';

export async function fetchTeamIndex(): Promise<TeamIndexResponse> {
  const { data } = await apiClient.get<TeamIndexResponse>('/team');
  return data;
}

export async function fetchTeamMember(id: number): Promise<TeamMemberDetailResponse> {
  const { data } = await apiClient.get<TeamMemberDetailResponse>(`/team/${id}`);
  return data;
}

export interface LeaveApprovalsResponse {
  leaves: LeaveRequestInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: { total: number; pending: number; approved: number; rejected: number };
}

export async function fetchLeaveApprovals(status = 'pending'): Promise<LeaveApprovalsResponse> {
  const { data } = await apiClient.get<LeaveApprovalsResponse>('/team/leave-approvals', { params: { status } });
  return data;
}

export async function approveLeave(id: number, note?: string): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/team/leaves/${id}/approve`, { note });
  return data;
}

export async function rejectLeave(id: number, reason: string): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/team/leaves/${id}/reject`, { reason });
  return data;
}

export interface ExpenseApprovalsResponse {
  expenses: ExpenseInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: { total: number; submitted: number; approved: number; paid: number; rejected: number };
  pending_total: number;
}

export async function fetchExpenseApprovals(status = 'submitted'): Promise<ExpenseApprovalsResponse> {
  const { data } = await apiClient.get<ExpenseApprovalsResponse>('/team/expense-approvals', { params: { status } });
  return data;
}

export async function approveExpense(id: number, note?: string): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/team/expenses/${id}/approve`, { note });
  return data;
}

export async function rejectExpense(id: number, reason: string): Promise<{ success: true; message: string }> {
  const { data } = await apiClient.post(`/team/expenses/${id}/reject`, { reason });
  return data;
}
