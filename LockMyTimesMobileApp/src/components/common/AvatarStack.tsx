import { Image, StyleSheet, Text, View } from 'react-native';
import { radii } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';

export interface AvatarStackPerson {
  id: number | string;
  name: string;
  avatar_url?: string | null;
}

/** Overlapping circular avatars with a "+N" overflow badge — used on task and project cards. */
export function AvatarStack({
  people,
  max = 3,
  size = 26,
}: {
  people: AvatarStackPerson[];
  max?: number;
  size?: number;
}) {
  const theme = useTheme();
  const shown = people.slice(0, max);
  const overflow = people.length - shown.length;

  return (
    <View style={styles.row}>
      {shown.map((p, i) => (
        <View
          key={p.id}
          style={[
            styles.circle,
            {
              width: size,
              height: size,
              borderRadius: size / 2,
              marginLeft: i === 0 ? 0 : -size * 0.35,
              borderColor: theme.surface,
              backgroundColor: theme.primaryMuted,
              zIndex: shown.length - i,
            },
          ]}
        >
          {p.avatar_url ? (
            <Image source={{ uri: p.avatar_url }} style={{ width: size, height: size, borderRadius: size / 2 }} />
          ) : (
            <Text style={{ color: theme.primary, fontSize: size * 0.4, fontWeight: '700' }}>
              {p.name.slice(0, 1).toUpperCase()}
            </Text>
          )}
        </View>
      ))}
      {overflow > 0 && (
        <View
          style={[
            styles.circle,
            {
              width: size,
              height: size,
              borderRadius: size / 2,
              marginLeft: -size * 0.35,
              borderColor: theme.surface,
              backgroundColor: theme.surfaceAlt,
            },
          ]}
        >
          <Text style={{ color: theme.textMuted, fontSize: size * 0.36, fontWeight: '700' }}>+{overflow}</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center' },
  circle: { alignItems: 'center', justifyContent: 'center', borderWidth: 1.5, overflow: 'hidden' },
});