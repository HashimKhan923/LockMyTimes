import { StyleSheet, Text, View } from 'react-native';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { useAuthStore } from '../../stores/authStore';
import { spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export function SuspendedScreen() {
  const theme = useTheme();
  const subscriptionError = useAuthStore((s) => s.subscriptionError);
  const clearSubscriptionError = useAuthStore((s) => s.setSubscriptionError);
  const logout = useAuthStore((s) => s.logout);

  return (
    <Screen>
      <View style={styles.center}>
        <Text style={[typography.heading, { color: theme.text, textAlign: 'center' }]}>
          Workspace unavailable
        </Text>
        <Text
          style={[
            typography.body,
            { color: theme.textMuted, textAlign: 'center', marginTop: spacing.sm },
          ]}
        >
          {subscriptionError?.message ?? 'Your workspace is currently unavailable. Please contact your admin.'}
        </Text>

        <View style={{ marginTop: spacing.xl, width: '100%' }}>
          <Button
            title="Try again"
            variant="secondary"
            onPress={() => clearSubscriptionError(null)}
          />
        </View>
        <View style={{ marginTop: spacing.sm, width: '100%' }}>
          <Button title="Sign out" variant="danger" onPress={() => logout()} />
        </View>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
});
