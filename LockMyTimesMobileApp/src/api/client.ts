import axios, { AxiosError } from 'axios';
import { useAuthStore } from '../stores/authStore';
import type { ApiErrorBody } from './types';

/**
 * Dev-only default. In a real build this should come from an env-specific
 * config (EAS build profiles / app.config.ts extra field), not hardcoded —
 * left simple here since there's no build pipeline yet.
 */
export const API_BASE_URL = 'https://lockmytimes.com/api/v1';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15000,
});

const PRE_AUTH_PATHS = ['/tenant/resolve', '/auth/login'];

apiClient.interceptors.request.use((config) => {
  const { tenantSlug, token } = useAuthStore.getState();
  const isPreAuth = PRE_AUTH_PATHS.some((p) => config.url?.startsWith(p));

  if (tenantSlug) {
    config.headers['X-Tenant'] = tenantSlug;
  }
  if (token && !isPreAuth) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  config.headers.Accept = 'application/json';

  return config;
});

export function extractErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const body = error.response?.data as ApiErrorBody | undefined;
    if (body?.errors) {
      const first = Object.values(body.errors)[0];
      if (first?.[0]) return first[0];
    }
    if (body?.message) return body.message;
  }
  return 'Something went wrong. Please try again.';
}

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorBody>) => {
    const status = error.response?.status;
    const body = error.response?.data;
    const store = useAuthStore.getState();

    if (status === 401) {
      // Token invalid/revoked — drop the session; RootNavigator's state
      // machine falls back to the login screen on its own.
      store.logout();
    } else if (status === 402 && body) {
      store.setSubscriptionError({
        status: body.status ?? 'suspended',
        message: body.message,
      });
    } else if (status === 423 && store.user) {
      // Server says a password change is mandatory — reflect it locally so
      // the navigator gate locks to ChangePasswordScreen immediately.
      store.updateUser({ ...store.user, must_change_password: true });
    }

    return Promise.reject(error);
  }
);
