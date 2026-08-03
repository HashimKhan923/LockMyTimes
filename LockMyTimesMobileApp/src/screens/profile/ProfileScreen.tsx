import { Icon } from '../../components/common/Icon';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as ImagePicker from 'expo-image-picker';
import { useEffect, useState } from 'react';
import { Image, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { fetchProfile, updateProfile, uploadAvatar } from '../../api/endpoints/profile';
import { Button } from '../../components/common/Button';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { TextField } from '../../components/common/TextField';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { useAuthStore } from '../../stores/authStore';
import type { MoreStackParamList } from '../../navigation/MoreStack';

type Props = NativeStackScreenProps<MoreStackParamList, 'Profile'>;

type Tab = 'personal' | 'address';

export function ProfileScreen({ navigation }: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const authUser = useAuthStore((s) => s.user);
  const updateAuthUser = useAuthStore((s) => s.updateUser);
  const [tab, setTab] = useState<Tab>('personal');

  const { data } = useQuery({ queryKey: ['profile'], queryFn: fetchProfile });
  const emp = data?.employee;

  const [preferredName, setPreferredName] = useState('');
  const [personalEmail, setPersonalEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [mobile, setMobile] = useState('');
  const [addressLine1, setAddressLine1] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('');
  const [postalCode, setPostalCode] = useState('');

  useEffect(() => {
    if (!emp) return;
    setPreferredName(emp.preferred_name ?? '');
    setPersonalEmail(emp.personal_email ?? '');
    setPhone(emp.phone ?? '');
    setMobile(emp.mobile ?? '');
    setAddressLine1(emp.address.line1 ?? '');
    setCity(emp.address.city ?? '');
    setState(emp.address.state ?? '');
    setPostalCode(emp.address.postal_code ?? '');
  }, [emp]);

  const saveMutation = useMutation({
    mutationFn: () =>
      updateProfile(
        tab === 'personal'
          ? { preferred_name: preferredName, personal_email: personalEmail, phone, mobile }
          : { address_line1: addressLine1, city, state, postal_code: postalCode }
      ),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['profile'] }),
  });

  const avatarMutation = useMutation({
    mutationFn: uploadAvatar,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
      // The dashboard header reads the avatar from the cached auth session
      // (set at login), not this screen's own /profile query — patch it
      // here too so the new photo shows up there immediately instead of
      // only after the next login.
      if (authUser) updateAuthUser({ ...authUser, avatar_url: data.employee.avatar_url });
    },
  });

  async function handlePickAvatar() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) return;

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.7,
      allowsEditing: true,
      aspect: [1, 1],
    });
    if (!result.canceled && result.assets?.[0]) {
      const asset = result.assets[0];
      avatarMutation.mutate({
        uri: asset.uri,
        name: asset.fileName ?? 'avatar.jpg',
        mimeType: asset.mimeType ?? 'image/jpeg',
      });
    }
  }

  if (!emp) return <Screen>{null}</Screen>;

  const initials = emp.full_name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroCenter}>
          <Pressable onPress={handlePickAvatar}>
            {emp.avatar_url ? (
              <Image source={{ uri: emp.avatar_url }} style={styles.avatar} />
            ) : (
              <View style={[styles.avatar, styles.avatarFallback]}>
                <Text style={styles.avatarInitials}>{initials}</Text>
              </View>
            )}
            <View style={[styles.editBadge, { backgroundColor: theme.primary, borderColor: '#FFFFFF' }]}>
              <Icon name="camera" size={14} color="#fff" />
            </View>
          </Pressable>
          <Text style={[typography.heading, { color: '#FFFFFF', marginTop: spacing.md, textAlign: 'center' }]}>
            {emp.full_name}
          </Text>
          <Text style={[typography.caption, { color: 'rgba(255,255,255,0.8)', marginTop: 2 }]}>
            ID: {emp.employee_code}
          </Text>
          {emp.department && (
            <View style={styles.deptPill}>
              <Text style={styles.deptPillText}>{emp.department}</Text>
            </View>
          )}
        </View>
      </HeroHeader>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.padded}>
        <View style={styles.tabRow}>
          {(['personal', 'address'] as Tab[]).map((t) => (
            <Pressable
              key={t}
              onPress={() => setTab(t)}
              style={[
                styles.tabChip,
                { backgroundColor: tab === t ? theme.primary : theme.surfaceAlt, borderColor: theme.border },
              ]}
            >
              <Text style={{ color: tab === t ? theme.onPrimary : theme.text, fontWeight: '600', textTransform: 'capitalize' }}>
                {t}
              </Text>
            </Pressable>
          ))}
        </View>

        {tab === 'personal' ? (
          <View style={{ marginTop: spacing.md }}>
            <TextField label="Preferred name" value={preferredName} onChangeText={setPreferredName} />
            <TextField
              label="Personal email"
              keyboardType="email-address"
              autoCapitalize="none"
              value={personalEmail}
              onChangeText={setPersonalEmail}
            />
            <TextField label="Phone" keyboardType="phone-pad" value={phone} onChangeText={setPhone} />
            <TextField label="Mobile" keyboardType="phone-pad" value={mobile} onChangeText={setMobile} />
          </View>
        ) : (
          <View style={{ marginTop: spacing.md }}>
            <TextField label="Address line 1" value={addressLine1} onChangeText={setAddressLine1} />
            <TextField label="City" value={city} onChangeText={setCity} />
            <TextField label="State" value={state} onChangeText={setState} />
            <TextField label="Postal code" value={postalCode} onChangeText={setPostalCode} />
          </View>
        )}

        {saveMutation.isError && (
          <Text style={[typography.caption, { color: theme.danger, marginBottom: spacing.sm }]}>
            {extractErrorMessage(saveMutation.error)}
          </Text>
        )}
        <Button title="Save changes" onPress={() => saveMutation.mutate()} loading={saveMutation.isPending} />

        <View style={styles.linksSection}>
          <Pressable
            onPress={() => navigation.navigate('ChangePassword')}
            style={[
              styles.linkRow,
              { backgroundColor: theme.surface },
              Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
            ]}
          >
            <Icon name="lock-closed-outline" size={18} color={theme.primary} />
            <Text style={[typography.body, { color: theme.text, flex: 1 }]}>Change password</Text>
            <Icon name="chevron-forward" size={16} color={theme.textMuted} />
          </Pressable>
          <Pressable
            onPress={() => navigation.navigate('EmergencyContacts')}
            style={[
              styles.linkRow,
              { backgroundColor: theme.surface },
              Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
            ]}
          >
            <Icon name="people-outline" size={18} color={theme.primary} />
            <Text style={[typography.body, { color: theme.text, flex: 1 }]}>Emergency contacts</Text>
            <Icon name="chevron-forward" size={16} color={theme.textMuted} />
          </Pressable>
        </View>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  heroCenter: { alignItems: 'center' },
  avatar: { width: 84, height: 84, borderRadius: 42, borderWidth: 3, borderColor: 'rgba(255,255,255,0.5)' },
  avatarFallback: { alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.22)' },
  avatarInitials: { color: '#FFFFFF', fontSize: 28, fontWeight: '700' },
  editBadge: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    width: 26,
    height: 26,
    borderRadius: 13,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  deptPill: {
    marginTop: spacing.sm,
    backgroundColor: 'rgba(255,255,255,0.22)',
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  deptPillText: { color: '#FFFFFF', fontWeight: '700', fontSize: 12 },
  tabRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg },
  tabChip: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderRadius: radii.pill, borderWidth: 1 },
  linksSection: { marginTop: spacing.lg, marginBottom: spacing.xl },
  linkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
});
