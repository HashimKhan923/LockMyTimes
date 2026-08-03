import { useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { extractErrorMessage } from '../../api/client';
import { updatePassword } from '../../api/endpoints/auth';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { useAuthStore } from '../../stores/authStore';
import { spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export function ChangePasswordScreen() {
  const theme = useTheme();
  const updateUser = useAuthStore((s) => s.updateUser);
  const isForced = useAuthStore((s) => s.user?.must_change_password ?? false);

  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');

  const mutation = useMutation({
    mutationFn: () =>
      updatePassword({
        current_password: isForced ? undefined : currentPassword,
        password,
        password_confirmation: confirmation,
      }),
    onSuccess: (data) => updateUser(data.user),
  });

  return (
    <Screen>
      <View style={styles.top}>
        <Text style={[typography.title, { color: theme.text }]}>
          {isForced ? 'Set a new password' : 'Change password'}
        </Text>
        <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs }]}>
          {isForced
            ? 'For your security, you need to set a new password before continuing.'
            : 'Enter your current password and choose a new one.'}
        </Text>
      </View>

      {!isForced && (
        <TextField
          label="Current password"
          secureTextEntry
          value={currentPassword}
          onChangeText={setCurrentPassword}
        />
      )}
      <TextField label="New password" secureTextEntry value={password} onChangeText={setPassword} />
      <TextField
        label="Confirm new password"
        secureTextEntry
        value={confirmation}
        onChangeText={setConfirmation}
      />

      {mutation.isError && (
        <Text style={[styles.error, { color: theme.danger }]}>
          {extractErrorMessage(mutation.error)}
        </Text>
      )}

      <View style={{ marginTop: spacing.md }}>
        <Button
          title="Save password"
          onPress={() => mutation.mutate()}
          loading={mutation.isPending}
          disabled={
            (!isForced && !currentPassword) || password.length < 8 || password !== confirmation
          }
        />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  top: { marginTop: spacing.xl, marginBottom: spacing.lg },
  error: { marginBottom: spacing.md, fontSize: 13 },
});
