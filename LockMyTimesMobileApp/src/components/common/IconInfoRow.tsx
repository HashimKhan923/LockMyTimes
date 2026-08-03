import type { ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Icon } from './Icon';
import { radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface IconInfoRowProps {
  icon: string;
  iconColor?: string;
  label: string;
  value: string;
  /** Omits the bottom divider — pass true on the last row in a card. */
  last?: boolean;
}

export function IconInfoRow({ icon, iconColor, label, value, last }: IconInfoRowProps) {
  const theme = useTheme();
  const color = iconColor ?? theme.primary;

  return (
    <View style={[styles.row, !last && { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: theme.border }]}>
      <View style={[styles.badge, { backgroundColor: color + '1A' }]}>
        <Icon name={icon} size={18} color={color} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[typography.caption, { color: theme.textMuted }]}>{label}</Text>
        <Text style={[typography.body, { color: theme.text, fontWeight: '600', marginTop: 2 }]}>{value}</Text>
      </View>
    </View>
  );
}

/** White rounded card wrapper for a group of IconInfoRow — pass rows as children. */
export function IconInfoCard({ children }: { children: ReactNode }) {
  const theme = useTheme();
  return <View style={[styles.card, { backgroundColor: theme.surface }]}>{children}</View>;
}

const styles = StyleSheet.create({
  card: { borderRadius: radii.lg, overflow: 'hidden' },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, padding: spacing.md },
  badge: { width: 40, height: 40, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
});
