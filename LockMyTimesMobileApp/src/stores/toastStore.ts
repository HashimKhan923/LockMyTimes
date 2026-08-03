import { create } from 'zustand';

export type ToastType = 'success' | 'error' | 'info';

interface ToastState {
  message: string | null;
  type: ToastType;
  /** Bumped on every show() so the same message string can re-trigger the enter animation. */
  key: number;
  show: (message: string, type?: ToastType) => void;
  hide: () => void;
}

export const useToastStore = create<ToastState>((set) => ({
  message: null,
  type: 'success',
  key: 0,
  show: (message, type = 'success') => set((s) => ({ message, type, key: s.key + 1 })),
  hide: () => set({ message: null }),
}));
