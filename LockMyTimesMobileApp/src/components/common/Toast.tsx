import { useEffect } from 'react';
import { StyleSheet, Text } from 'react-native';
import { AnimatePresence, MotiView } from 'moti';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Icon } from './Icon';
import { radii, spacing } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useToastStore, type ToastType } from '../../stores/toastStore';

const ICON_BY_TYPE: Record<ToastType, string> = {
  success: 'checkmark-done-outline',
  error: 'alert-circle',
  info: 'notifications-outline',
};

/**
 * Mounted once at the app root (see App.tsx) so any screen can trigger a
 * toast via useToastStore.getState().show(...) without prop-drilling or a
 * per-screen banner. Auto-dismisses; tapping isn't needed since it's brief.
 */
export function Toast() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const { message, type, key, hide } = useToastStore();

  useEffect(() => {
    if (!message) return;
    const id = setTimeout(hide, 2800);
    return () => clearTimeout(id);
  }, [message, key, hide]);

  const bg = type === 'error' ? theme.danger : type === 'info' ? theme.primary : theme.success;

  return (
    <AnimatePresence>
      {message && (
        <MotiView
          key={key}
          from={{ opacity: 0, translateY: -16 }}
          animate={{ opacity: 1, translateY: 0 }}
          exit={{ opacity: 0, translateY: -16 }}
          transition={{ type: 'timing', duration: 220 }}
          style={[styles.wrap, { top: insets.top + spacing.sm }]}
          pointerEvents="none"
        >
          <MotiView style={[styles.pill, { backgroundColor: bg }]}>
            <Icon name={ICON_BY_TYPE[type]} size={16} color="#FFFFFF" />
            <Text style={styles.text} numberOfLines={2}>
              {message}
            </Text>
          </MotiView>
        </MotiView>
      )}
    </AnimatePresence>
  );
}

const styles = StyleSheet.create({
  wrap: { position: 'absolute', left: spacing.md, right: spacing.md, zIndex: 9999, alignItems: 'center' },
  pill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm + 2,
    paddingHorizontal: spacing.md,
    borderRadius: radii.lg,
    maxWidth: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 8,
  },
  text: { color: '#FFFFFF', fontWeight: '700', fontSize: 13, flexShrink: 1 },
});
