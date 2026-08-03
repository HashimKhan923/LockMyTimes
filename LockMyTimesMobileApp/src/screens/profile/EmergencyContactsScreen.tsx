import { Icon } from '../../components/common/Icon';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { FlatList, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import {
  createEmergencyContact,
  deleteEmergencyContact,
  fetchProfile,
} from '../../api/endpoints/profile';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { StatusBadge } from '../../components/common/StatusBadge';
import { TextField } from '../../components/common/TextField';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import type { EmergencyContactInfo } from '../../api/types';

type Props = NativeStackScreenProps<MoreStackParamList, 'EmergencyContacts'>;

export function EmergencyContactsScreen({}: Props) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [name, setName] = useState('');
  const [relationship, setRelationship] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [address, setAddress] = useState('');

  const { data } = useQuery({ queryKey: ['profile'], queryFn: fetchProfile });

  const createMutation = useMutation({
    mutationFn: () =>
      createEmergencyContact({
        name,
        relationship,
        phone,
        email: email.trim() || undefined,
        address: address.trim() || undefined,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] });
      setShowForm(false);
      setName('');
      setRelationship('');
      setPhone('');
      setEmail('');
      setAddress('');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteEmergencyContact(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['profile'] }),
  });

  function renderContact({ item, index }: { item: EmergencyContactInfo; index: number }) {
    return (
      <MotiView {...entranceStagger(index)}>
        <View
          style={[
            styles.card,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={{ flex: 1 }}>
            <View style={styles.nameRow}>
              <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>{item.name}</Text>
              {item.is_primary && (
                <View style={{ marginLeft: spacing.xs }}>
                  <StatusBadge value="primary" label="Primary" color={theme.primary} filled />
                </View>
              )}
            </View>
            <Text style={[typography.caption, { color: theme.textMuted }]}>
              {item.relationship} · {item.phone}
            </Text>
            {item.email && (
              <Text style={[typography.caption, { color: theme.textMuted }]}>{item.email}</Text>
            )}
            {item.address && (
              <Text style={[typography.caption, { color: theme.textMuted }]} numberOfLines={2}>
                {item.address}
              </Text>
            )}
          </View>
          <Pressable onPress={() => deleteMutation.mutate(item.id)}>
            <Icon name="trash-outline" size={20} color={theme.danger} />
          </Pressable>
        </View>
      </MotiView>
    );
  }

  return (
    <Screen padded={false}>
      <View style={styles.padded}>
        <Text style={[typography.title, { color: theme.text, marginTop: spacing.md }]}>Emergency contacts</Text>
      </View>

      <FlatList
        data={data?.emergency_contacts ?? []}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderContact}
        contentContainerStyle={styles.padded}
        ListEmptyComponent={
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
            No emergency contacts yet.
          </Text>
        }
        ListFooterComponent={
          <View style={{ marginTop: spacing.md }}>
            {showForm ? (
              <View
                style={[
                  styles.card,
                  { flexDirection: 'column', backgroundColor: theme.surface },
                  Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
                ]}
              >
                <TextField label="Name" value={name} onChangeText={setName} />
                <TextField label="Relationship" value={relationship} onChangeText={setRelationship} />
                <TextField label="Phone" keyboardType="phone-pad" value={phone} onChangeText={setPhone} />
                <TextField
                  label="Email (optional)"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={email}
                  onChangeText={setEmail}
                />
                <TextField label="Address (optional)" value={address} onChangeText={setAddress} />
                {createMutation.isError && (
                  <Text style={[typography.caption, { color: theme.danger, marginBottom: spacing.sm }]}>
                    {extractErrorMessage(createMutation.error)}
                  </Text>
                )}
                <Button
                  title="Add contact"
                  onPress={() => createMutation.mutate()}
                  loading={createMutation.isPending}
                  disabled={!name.trim() || !relationship.trim() || !phone.trim()}
                />
              </View>
            ) : (
              <Button
                title="Add emergency contact"
                variant="secondary"
                onPress={() => setShowForm(true)}
                disabled={(data?.emergency_contacts.length ?? 0) >= 5}
              />
            )}
          </View>
        }
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  nameRow: { flexDirection: 'row', alignItems: 'center' },
});
