import { useMutation } from '@tanstack/react-query';
import * as Device from 'expo-device';
import { useState } from 'react';
import { Image, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { MotiView } from 'moti';
import { extractErrorMessage } from '../../api/client';
import { login } from '../../api/endpoints/auth';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { useAuthStore } from '../../stores/authStore';
import { entranceStagger, heroSpring } from '../../theme/motion';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export function LoginScreen() {
  const theme = useTheme();
  const setSession = useAuthStore((s) => s.setSession);
  const clearTenantSlug = useAuthStore((s) => s.clearTenantSlug);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const mutation = useMutation({
    mutationFn: () =>
      login({
        email: email.trim(),
        password,
        device_name: Device.deviceName ?? 'Mobile device',
      }),
    onSuccess: (data) => setSession(data.token, data.user),
  });

  return (
    <Screen>
      <MotiView
        from={{ opacity: 0, scale: 0.7, rotate: '-8deg' }}
        animate={{ opacity: 1, scale: 1, rotate: '0deg' }}
        transition={heroSpring}
        style={styles.logoOuter}
      >
        <LinearGradient
          colors={theme.gradients.accent}
          start={{ x: 0.1, y: 0 }}
          end={{ x: 0.9, y: 1 }}
          style={[
            styles.logoWrap,
            Platform.select({
              ios: { shadowColor: theme.primary, shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.4, shadowRadius: 24 },
              android: { elevation: 10, shadowColor: theme.primary },
            }),
          ]}
        >
          <Image source={require('../../../assets/splash-icon.png')} style={styles.logo} resizeMode="contain" />
        </LinearGradient>
      </MotiView>

      <MotiView {...entranceStagger(0)} style={styles.top}>
        <Text style={[typography.title, { color: theme.text, textAlign: 'center' }]}>Welcome back</Text>
        <Text
          style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs, textAlign: 'center' }]}
        >
          Use the email and password your HR team gave you.
        </Text>
      </MotiView>

      <MotiView {...entranceStagger(1)}>
        <TextField
          label="Email"
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="email-address"
          value={email}
          onChangeText={setEmail}
        />
        <TextField label="Password" secureTextEntry value={password} onChangeText={setPassword} />
      </MotiView>

      {mutation.isError && (
        <MotiView
          from={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ type: 'timing', duration: 200 }}
          style={[styles.errorChip, { backgroundColor: theme.danger + '1A', borderColor: theme.danger + '33' }]}
        >
          <Text style={[typography.caption, { color: theme.danger }]}>{extractErrorMessage(mutation.error)}</Text>
        </MotiView>
      )}

      <MotiView {...entranceStagger(2)} style={{ marginTop: spacing.md }}>
        <Button
          title="Sign in"
          onPress={() => mutation.mutate()}
          loading={mutation.isPending}
          disabled={!email.trim() || !password}
        />
      </MotiView>

      <Pressable style={styles.switchCompany} onPress={() => clearTenantSlug()}>
        <Text style={[typography.caption, { color: theme.textMuted }]}>Not your company? Switch company code</Text>
      </Pressable>
    </Screen>
  );
}

const styles = StyleSheet.create({
  logoOuter: { alignSelf: 'center', marginTop: spacing.xl },
  logoWrap: {
    width: 76,
    height: 76,
    borderRadius: radii.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: { width: 46, height: 46 },
  top: { marginTop: spacing.lg, marginBottom: spacing.lg, alignItems: 'center' },
  errorChip: { borderRadius: radii.md, padding: spacing.sm + 2, marginBottom: spacing.md, borderWidth: 1 },
  switchCompany: { marginTop: spacing.lg, alignItems: 'center' },
});
