import * as SecureStore from 'expo-secure-store';
import { create } from 'zustand';
import type { UserProfile } from '../api/types';

const STORAGE_KEY = 'lmt_auth_v1';

interface StoredAuth {
  tenantSlug: string;
  token: string;
  user: UserProfile;
}

export interface SubscriptionError {
  status: string;
  message: string;
}

interface AuthState {
  isHydrated: boolean;
  tenantSlug: string | null;
  token: string | null;
  user: UserProfile | null;
  isManager: boolean;
  subscriptionError: SubscriptionError | null;

  hydrate: () => Promise<void>;
  setTenantSlug: (slug: string) => Promise<void>;
  clearTenantSlug: () => Promise<void>;
  setSession: (token: string, user: UserProfile) => Promise<void>;
  updateUser: (user: UserProfile) => Promise<void>;
  setIsManager: (isManager: boolean) => void;
  setSubscriptionError: (error: SubscriptionError | null) => void;
  logout: () => Promise<void>;
}

async function persist(data: StoredAuth) {
  await SecureStore.setItemAsync(STORAGE_KEY, JSON.stringify(data));
}

async function clearPersisted() {
  await SecureStore.deleteItemAsync(STORAGE_KEY);
}

export const useAuthStore = create<AuthState>((set, get) => ({
  isHydrated: false,
  tenantSlug: null,
  token: null,
  user: null,
  isManager: false,
  subscriptionError: null,

  hydrate: async () => {
    try {
      const raw = await SecureStore.getItemAsync(STORAGE_KEY);
      if (raw) {
        const parsed: StoredAuth = JSON.parse(raw);
        set({
          tenantSlug: parsed.tenantSlug,
          token: parsed.token,
          user: parsed.user,
        });
      }
    } finally {
      set({ isHydrated: true });
    }
  },

  setTenantSlug: async (slug: string) => {
    set({ tenantSlug: slug });
    const { token, user } = get();
    if (token && user) {
      await persist({ tenantSlug: slug, token, user });
    }
  },

  clearTenantSlug: async () => {
    set({ tenantSlug: null, token: null, user: null, isManager: false });
    await clearPersisted();
  },

  setSession: async (token: string, user: UserProfile) => {
    const tenantSlug = get().tenantSlug;
    set({ token, user });
    if (tenantSlug) {
      await persist({ tenantSlug, token, user });
    }
  },

  updateUser: async (user: UserProfile) => {
    set({ user });
    const { tenantSlug, token } = get();
    if (tenantSlug && token) {
      await persist({ tenantSlug, token, user });
    }
  },

  setIsManager: (isManager: boolean) => set({ isManager }),

  setSubscriptionError: (subscriptionError: SubscriptionError | null) => set({ subscriptionError }),

  logout: async () => {
    const tenantSlug = get().tenantSlug;
    set({ token: null, user: null, isManager: false });
    // Keep the tenant slug so the user lands back on the login screen,
    // not the company-code screen, after signing out.
    if (tenantSlug) {
      await SecureStore.deleteItemAsync(STORAGE_KEY);
    }
  },
}));
