import { apiClient } from '../client';
import type { LoginResponse, UserProfile } from '../types';

export interface LoginPayload {
  email: string;
  password: string;
  device_name: string;
  device_token?: string;
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/login', payload);
  return data;
}

export interface UpdatePasswordPayload {
  current_password?: string;
  password: string;
  password_confirmation: string;
}

export async function updatePassword(
  payload: UpdatePasswordPayload
): Promise<{ message: string; user: UserProfile }> {
  const { data } = await apiClient.put('/auth/password', payload);
  return data;
}

export async function logoutRequest(): Promise<void> {
  await apiClient.post('/auth/logout');
}

export async function signOutOtherSessions(): Promise<{ message: string }> {
  const { data } = await apiClient.post('/auth/sign-out-other-sessions');
  return data;
}
