import { useEffect } from 'react';
import { ActivityIndicator, View } from 'react-native';
import { AuthStack } from './AuthStack';
import { MainTabs } from './MainTabs';
import { CompanyCodeScreen } from '../screens/onboarding/CompanyCodeScreen';
import { ChangePasswordScreen } from '../screens/auth/ChangePasswordScreen';
import { SuspendedScreen } from '../screens/auth/SuspendedScreen';
import { useAuthStore } from '../stores/authStore';
import { useTheme } from '../theme/useTheme';

/**
 * State-machine switch (see build plan B2) — not a single stack. Exactly one
 * of these renders at a time, driven entirely by authStore state:
 *   splash -> no tenantSlug -> no token -> suspended -> must_change_password -> MainTabs
 */
export function RootNavigator() {
  const theme = useTheme();
  const isHydrated = useAuthStore((s) => s.isHydrated);
  const hydrate = useAuthStore((s) => s.hydrate);
  const tenantSlug = useAuthStore((s) => s.tenantSlug);
  const token = useAuthStore((s) => s.token);
  const subscriptionError = useAuthStore((s) => s.subscriptionError);
  const mustChangePassword = useAuthStore((s) => s.user?.must_change_password ?? false);

  useEffect(() => {
    hydrate();
  }, [hydrate]);

  if (!isHydrated) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.background }}>
        <ActivityIndicator size="large" color={theme.primary} />
      </View>
    );
  }

  if (!tenantSlug) {
    return <CompanyCodeScreen />;
  }

  if (!token) {
    return <AuthStack />;
  }

  if (subscriptionError) {
    return <SuspendedScreen />;
  }

  if (mustChangePassword) {
    return <ChangePasswordScreen />;
  }

  return <MainTabs />;
}
