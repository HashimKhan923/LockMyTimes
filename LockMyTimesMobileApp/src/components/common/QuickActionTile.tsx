import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Icon } from './Icon';
import { radii, spacing, typography } from '../../theme/tokens';

export interface QuickActionTileProps {
  icon: string;
  label: string;
  color: string;
  onPress: () => void;
}

/** Soft pastel-tinted square tile with a centered icon badge — used in a 2-column grid. */
export function QuickActionTile({ icon, label, color, onPress }: QuickActionTileProps) {
  return (
    <Pressable onPress={onPress} style={[styles.tile, { backgroundColor: color + '17' }]}>
      <View style={[styles.iconBadge, { backgroundColor: color + '26' }]}>
        <Icon name={icon} size={22} color={color} />
      </View>
      <Text style={[typography.subheading, { color, marginTop: spacing.sm }]}>{label}</Text>
    </Pressable>
  );
}

export const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  tile: {
    flexBasis: '47%',
    flexGrow: 1,
    borderRadius: radii.lg,
    padding: spacing.md,
    alignItems: 'flex-start',
  },
  iconBadge: { width: 44, height: 44, borderRadius: radii.md, alignItems: 'center', justifyContent: 'center' },
});
