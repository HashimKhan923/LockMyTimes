import { apiClient } from '../client';
import type { TenantInfo } from '../types';

export async function resolveTenant(slug: string): Promise<TenantInfo> {
  const { data } = await apiClient.post<TenantInfo>(
    '/tenant/resolve',
    {},
    { headers: { 'X-Tenant': slug } }
  );
  return data;
}
