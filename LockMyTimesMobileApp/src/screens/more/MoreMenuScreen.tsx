import { Icon } from '../../components/common/Icon';
import { useQuery } from '@tanstack/react-query';
import { Image, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { fetchTeamIndex } from '../../api/endpoints/team';
import { HeroHeader } from '../../components/common/HeroHeader';
import { Screen } from '../../components/common/Screen';
import { logoutRequest } from '../../api/endpoints/auth';
import { useAuthStore } from '../../stores/authStore';
import { entranceStagger } from '../../theme/motion';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import type { MoreStackParamList } from '../../navigation/MoreStack';
import { useResetOnTabBlur } from '../../navigation/useResetOnTabBlur';

type Props = NativeStackScreenProps<MoreStackParamList, 'MoreMenu'>;

interface MenuItem {
  label: string;
  icon: string;
  color: (theme: ReturnType<typeof useTheme>) => string;
  onPress: () => void;
}

export function MoreMenuScreen({ navigation }: Props) {
  useResetOnTabBlur(navigation);
  const theme = useTheme();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  // Only fetched to decide whether "My Team" should appear at all — a
  // non-manager should never see an entry that just 403s (see build plan
  // B2: "cache isManager rather than always showing it").
  const { data: teamData } = useQuery({ queryKey: ['team', 'index'], queryFn: fetchTeamIndex });

  const handleLogout = async () => {
    try {
      await logoutRequest();
    } finally {
      logout();
    }
  };

  const items: MenuItem[] = [
    { label: 'Profile', icon: 'person-outline', color: (t) => t.primary, onPress: () => navigation.navigate('Profile') },
    ...(teamData?.is_manager
      ? [{ label: 'My Team', icon: 'people-circle-outline' as const, color: (t: ReturnType<typeof useTheme>) => t.accentBlue, onPress: () => navigation.navigate('Team') }]
      : []),
    { label: 'Payslips', icon: 'document-text-outline', color: (t) => t.categorical[4], onPress: () => navigation.navigate('PayslipList') },
    { label: 'Expenses', icon: 'receipt-outline', color: (t) => t.categorical[1], onPress: () => navigation.navigate('ExpenseList') },
    { label: 'Loans & Advances', icon: 'cash-outline', color: (t) => t.categorical[5], onPress: () => navigation.navigate('LoanList') },
    {
      label: 'Announcements',
      icon: 'megaphone-outline',
      color: (t) => t.categorical[3],
      onPress: () => navigation.navigate('Announcements', { screen: 'AnnouncementList' }),
    },
    { label: 'Notifications', icon: 'notifications-outline', color: (t) => t.categorical[6], onPress: () => navigation.navigate('Notifications') },
    { label: 'Settings', icon: 'settings-outline', color: (t) => t.textMuted, onPress: () => navigation.navigate('Settings') },
  ];

  const initials = (user?.name ?? '')
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  return (
    <Screen padded={false}>
      <HeroHeader>
        <View style={styles.heroRow}>
          {user?.avatar_url ? (
            <Image source={{ uri: user.avatar_url }} style={styles.avatar} />
          ) : (
            <View style={[styles.avatar, styles.avatarFallback]}>
              <Text style={styles.avatarInitials}>{initials}</Text>
            </View>
          )}
          <View style={{ marginLeft: spacing.sm }}>
            <Text style={[typography.title, { color: '#FFFFFF' }]}>More</Text>
            <Text style={[typography.caption, { color: 'rgba(255,255,255,0.85)' }]}>{user?.name}</Text>
          </View>
        </View>
      </HeroHeader>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.padded}>
        {items.map((item, i) => {
          const color = item.color(theme);
          return (
            <MotiView key={item.label} {...entranceStagger(i)}>
              <Pressable
                onPress={item.onPress}
                style={[
                  styles.row,
                  { backgroundColor: theme.surface },
                  Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
                ]}
              >
                <View style={[styles.iconBadge, { backgroundColor: color + '1A' }]}>
                  <Icon name={item.icon} size={19} color={color} />
                </View>
                <Text style={[typography.body, { color: theme.text, flex: 1, fontWeight: '600' }]}>{item.label}</Text>
                <Icon name="chevron-forward" size={18} color={theme.textMuted} />
              </Pressable>
            </MotiView>
          );
        })}

        <Pressable
          onPress={handleLogout}
          style={[
            styles.row,
            styles.logoutRow,
            { backgroundColor: theme.surface },
            Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
          ]}
        >
          <View style={[styles.iconBadge, { backgroundColor: theme.danger + '1A' }]}>
            <Icon name="log-out-outline" size={19} color={theme.danger} />
          </View>
          <Text style={[typography.body, { color: theme.danger, flex: 1, fontWeight: '700' }]}>Sign out</Text>
        </Pressable>
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  padded: { paddingHorizontal: 24, paddingTop: spacing.md },
  heroRow: { flexDirection: 'row', alignItems: 'center' },
  avatar: { width: 52, height: 52, borderRadius: radii.pill, borderWidth: 2, borderColor: 'rgba(255,255,255,0.5)' },
  avatarFallback: { alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.22)' },
  avatarInitials: { color: '#FFFFFF', fontSize: 18, fontWeight: '700' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  iconBadge: { width: 38, height: 38, borderRadius: radii.md, alignItems: 'center', justifyContent: 'center' },
  logoutRow: { marginTop: spacing.sm },
});
