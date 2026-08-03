import { apiClient } from '../client';
import type { EmergencyContactInfo, EmployeeProfile, ProfileIndexResponse } from '../types';

export async function fetchProfile(): Promise<ProfileIndexResponse> {
  const { data } = await apiClient.get<ProfileIndexResponse>('/profile');
  return data;
}

export interface UpdateProfilePayload {
  preferred_name?: string;
  personal_email?: string;
  phone?: string;
  mobile?: string;
  marital_status?: string;
  nationality?: string;
  date_of_birth?: string;
  address_line1?: string;
  address_line2?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
}

export async function updateProfile(
  payload: UpdateProfilePayload
): Promise<{ message: string; employee: EmployeeProfile }> {
  const { data } = await apiClient.put('/profile', payload);
  return data;
}

export async function uploadAvatar(
  file: { uri: string; name: string; mimeType: string | null }
): Promise<{ message: string; employee: EmployeeProfile }> {
  const form = new FormData();
  form.append('avatar', {
    uri: file.uri,
    name: file.name,
    type: file.mimeType ?? 'image/jpeg',
  } as unknown as Blob);

  const { data } = await apiClient.post('/profile/avatar', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return data;
}

export async function removeAvatar(): Promise<{ message: string; employee: EmployeeProfile }> {
  const { data } = await apiClient.delete('/profile/avatar');
  return data;
}

export interface EmergencyContactPayload {
  name: string;
  relationship: string;
  phone: string;
  email?: string;
  address?: string;
  is_primary?: boolean;
}

export async function createEmergencyContact(
  payload: EmergencyContactPayload
): Promise<{ message: string; contact: EmergencyContactInfo }> {
  const { data } = await apiClient.post('/profile/emergency-contacts', payload);
  return data;
}

export async function updateEmergencyContact(
  id: number,
  payload: EmergencyContactPayload
): Promise<{ message: string; contact: EmergencyContactInfo }> {
  const { data } = await apiClient.put(`/profile/emergency-contacts/${id}`, payload);
  return data;
}

export async function deleteEmergencyContact(id: number): Promise<{ message: string }> {
  const { data } = await apiClient.delete(`/profile/emergency-contacts/${id}`);
  return data;
}
