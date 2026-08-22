import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { NavigationContainer } from '@react-navigation/native';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import * as Notifications from 'expo-notifications';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { Linking, LogBox } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { RootNavigator } from './src/navigation/RootNavigator';
import { Toast } from './src/components/common/Toast';
import { useAuthStore } from './src/stores/authStore';
import { registerPushToken } from './src/api/endpoints/notifications';
import { registerForPushNotificationsAsync } from './src/utils/pushNotifications';

SplashScreen.preventAutoHideAsync().catch(() => {});

// Fired by moti's unused MotiSafeAreaView export merely importing `SafeAreaView`
// from 'react-native' — not from anything this app renders (Screen.tsx already
// uses react-native-safe-area-context). Safe to silence.
LogBox.ignoreLogs(["SafeAreaView has been deprecated"]);

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { retry: 1, staleTime: 30_000 },
  },
});

export default function App() {
  const isHydrated = useAuthStore((s) => s.isHydrated);
  const token = useAuthStore((s) => s.token);

  useEffect(() => {
    if (isHydrated) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [isHydrated]);

  // Register (or refresh) this device's push token whenever a session
  // becomes active — covers both a fresh login and reopening the app with
  // an already-persisted session. Best-effort: permission denial or a
  // failed registration call should never block using the app.
  useEffect(() => {
    if (!token) return;

    registerForPushNotificationsAsync()
      .then((pushToken) => {
        if (pushToken) return registerPushToken(pushToken);
      })
      .catch(() => {});
  }, [token]);

  // Tapping a push notification (app backgrounded/killed) opens the same
  // action_url the in-app notification list opens on tap.
  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const actionUrl = response.notification.request.content.data?.action_url;
      if (typeof actionUrl === 'string' && actionUrl) {
        Linking.openURL(actionUrl).catch(() => {});
      }
    });
    return () => subscription.remove();
  }, []);

  // Refresh the in-app feed the moment a push arrives in the foreground, so
  // the bell badge stays accurate without waiting for its 60s poll.
  useEffect(() => {
    const subscription = Notifications.addNotificationReceivedListener(() => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
    });
    return () => subscription.remove();
  }, []);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <NavigationContainer>
            <RootNavigator />
          </NavigationContainer>
          <Toast />
          <StatusBar style="auto" />
        </QueryClientProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
