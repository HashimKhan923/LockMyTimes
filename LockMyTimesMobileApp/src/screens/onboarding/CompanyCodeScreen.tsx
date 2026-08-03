import { useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { Image, Platform, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { MotiView } from 'moti';
import { heroSpring } from '../../theme/motion';
import { resolveTenant } from '../../api/endpoints/tenant';
import { extractErrorMessage } from '../../api/client';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { TextField } from '../../components/common/TextField';
import { useAuthStore } from '../../stores/authStore';
import { elevatedShadow, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { TenantInfo } from '../../api/types';

export function CompanyCodeScreen() {
  const theme = useTheme();
  const setTenantSlug = useAuthStore((s) => s.setTenantSlug);
  const [slug, setSlug] = useState('');
  const [confirmed, setConfirmed] = useState<TenantInfo | null>(null);

  const mutation = useMutation({
    mutationFn: (value: string) => resolveTenant(value.trim().toLowerCase()),
    onSuccess: (data) => setConfirmed(data),
  });

  const handleContinue = () => {
    if (confirmed) {
      setTenantSlug(slug.trim().toLowerCase());
      return;
    }
    if (slug.trim()) {
      mutation.mutate(slug);
    }
  };

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

      <View style={styles.top}>
        <Text style={[typography.title, { color: theme.text }]}>Welcome</Text>
        <Text style={[typography.body, { color: theme.textMuted, marginTop: spacing.xs }]}>
          Enter your company code to get started. Your HR team can give you this.
        </Text>
      </View>

      <TextField
        label="Company code"
        placeholder="e.g. acme"
        autoCapitalize="none"
        autoCorrect={false}
        value={slug}
        editable={!confirmed}
        onChangeText={(value) => {
          setSlug(value);
          if (confirmed) setConfirmed(null);
        }}
      />

      {mutation.isError && (
        <Text style={[styles.error, { color: theme.danger }]}>
          {extractErrorMessage(mutation.error)}
        </Text>
      )}

      {confirmed && (
        <MotiView
          from={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ type: 'spring', damping: 16 }}
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <Image source={{ uri: confirmed.logo_url }} style={styles.companyLogo} />
          <View style={{ flex: 1 }}>
            <Text style={[typography.subheading, { color: theme.text }]}>{confirmed.company_name}</Text>
            <View style={{ marginTop: 4, alignSelf: 'flex-start' }}>
              <StatusBadge
                value={confirmed.status}
                label={confirmed.status === 'trial' ? 'On trial' : 'Active workspace'}
                color={confirmed.status === 'trial' ? theme.warning : theme.success}
                filled
              />
            </View>
          </View>
        </MotiView>
      )}

      <View style={{ marginTop: spacing.lg }}>
        <Button
          title={confirmed ? 'Continue' : 'Find my company'}
          onPress={handleContinue}
          loading={mutation.isPending}
          disabled={!slug.trim()}
        />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  logoOuter: { alignSelf: 'center', marginTop: spacing.xl },
  logoWrap: { width: 68, height: 68, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
  logo: { width: 40, height: 40 },
  top: { marginTop: spacing.lg, marginBottom: spacing.lg },
  error: { marginTop: -spacing.sm, marginBottom: spacing.md, fontSize: 13 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: spacing.md,
    borderRadius: 20,
    gap: spacing.md,
  },
  companyLogo: { width: 44, height: 44, borderRadius: 10 },
});
