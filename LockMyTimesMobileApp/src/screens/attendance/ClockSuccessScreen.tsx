import { LinearGradient } from 'expo-linear-gradient';
import { useEffect } from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Icon } from '../../components/common/Icon';
import { typography } from '../../theme/tokens';
import { successBurst } from '../../theme/motion';
import { useTheme } from '../../theme/useTheme';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'Success'>;

/**
 * Flagship animated moment — a ring of concentric pulses radiates out from
 * a glowing checkmark, then the sheet auto-dismisses back to Attendance home.
 */
export function ClockSuccessScreen({ route, navigation }: Props) {
  const theme = useTheme();

  useEffect(() => {
    const timer = setTimeout(() => {
      navigation.popToTop();
    }, 1500);
    return () => clearTimeout(timer);
  }, [navigation]);

  return (
    <View style={[styles.backdrop, { backgroundColor: theme.background }]}>
      {[0, 1, 2].map((i) => (
        <MotiView
          key={i}
          from={{ opacity: 0.5, scale: 0.6 }}
          animate={{ opacity: 0, scale: 1.9 }}
          transition={{ type: 'timing', duration: 1400, delay: i * 220 }}
          style={[styles.ring, { borderColor: theme.success }]}
        />
      ))}

      <MotiView
        from={{ opacity: 0, scale: 0.5, rotate: '-20deg' }}
        animate={{ opacity: 1, scale: 1, rotate: '0deg' }}
        transition={successBurst}
        style={[
          styles.glow,
          Platform.select({
            ios: { shadowColor: theme.success, shadowOffset: { width: 0, height: 0 }, shadowOpacity: 0.55, shadowRadius: 30 },
            android: { elevation: 16, shadowColor: theme.success },
          }),
        ]}
      >
        <LinearGradient colors={theme.gradients.success} style={styles.circle}>
          <Icon name="checkmark" weight="bold" size={54} color={theme.onPrimary} />
        </LinearGradient>
      </MotiView>

      <MotiView
        from={{ opacity: 0, translateY: 10 }}
        animate={{ opacity: 1, translateY: 0 }}
        transition={{ type: 'timing', duration: 280, delay: 200 }}
      >
        <Text style={[typography.heading, { color: theme.text, marginTop: 28, textAlign: 'center' }]}>
          {route.params.message}
        </Text>
      </MotiView>
    </View>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  glow: { borderRadius: 55 },
  ring: {
    position: 'absolute',
    width: 110,
    height: 110,
    borderRadius: 55,
    borderWidth: 2,
  },
  circle: {
    width: 110,
    height: 110,
    borderRadius: 55,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
