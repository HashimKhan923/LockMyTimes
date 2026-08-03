import { Platform, StyleSheet, Text, View } from 'react-native';
import { Icon } from './Icon';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface StatCircleTileProps {
  icon?: string;
  value: string | number;
  label: string;
  color: string;
}

/** White stat tile with a small colored icon circle above the number — matches the reference's "This Month" / leave-summary rows. */
export function StatCircleTile({ icon, value, label, color }: StatCircleTileProps) {
  const theme = useTheme();
  return (
    <View
      style={[
        styles.tile,
        { backgroundColor: theme.surface },
        Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
      ]}
    >
      {icon && (
        <View style={[styles.dot, { backgroundColor: color + '1F' }]}>
          <Icon name={icon} size={14} color={color} weight="bold" />
        </View>
      )}
      <Text style={[typography.heading, { color, marginTop: icon ? spacing.xs : 0 }]}>{value}</Text>
      <Text style={[typography.caption, { color: theme.textMuted, marginTop: 2 }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  tile: { flex: 1, borderRadius: radii.md, padding: spacing.sm, alignItems: 'center' },
  dot: { width: 24, height: 24, borderRadius: radii.pill, alignItems: 'center', justifyContent: 'center' },
});
