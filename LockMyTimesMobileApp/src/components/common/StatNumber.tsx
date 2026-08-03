import { Platform, Text, View, type StyleProp, type ViewStyle } from 'react-native';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface StatNumberProps {
  value: string | number;
  label: string;
  /** Overrides theme.text for the value (e.g. theme.danger for "Overdue"). */
  color?: string;
  /** 'md' = regular stat-tile size (today's typography.heading). 'lg' = hero/dashboard-sized big number. */
  size?: 'md' | 'lg';
  /** Use when placed on a colored/gradient background instead of relying on theme.textMuted. */
  labelColor?: string;
  style?: StyleProp<ViewStyle>;
}

export function StatNumber({ value, label, color, labelColor, size = 'md', style }: StatNumberProps) {
  const theme = useTheme();
  const valueStyle = size === 'lg' ? { fontSize: 40, fontWeight: '800' as const } : typography.heading;

  return (
    <View style={style}>
      <Text style={[valueStyle, { color: color ?? theme.text }]}>{value}</Text>
      <Text style={[typography.caption, { color: labelColor ?? theme.textMuted, marginTop: 2 }]}>{label}</Text>
    </View>
  );
}

/** Drop-in replacement for the local `Stat`/`statCard` pattern (ProjectDetailScreen, TeamMemberDetailScreen). */
export function StatTile({ value, label, color }: { value: string | number; label: string; color?: string }) {
  const theme = useTheme();
  return (
    <View
      style={[
        {
          flex: 1,
          borderRadius: radii.md,
          padding: spacing.sm,
          alignItems: 'center',
          backgroundColor: theme.surface,
        },
        Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
      ]}
    >
      <StatNumber value={value} label={label} color={color} />
    </View>
  );
}
