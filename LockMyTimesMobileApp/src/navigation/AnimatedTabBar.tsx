import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { LinearGradient } from 'expo-linear-gradient';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, { useAnimatedStyle, withSpring, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Icon } from '../components/common/Icon';
import { radii } from '../theme/tokens';
import { useTheme } from '../theme/useTheme';

const AnimatedGradient = Animated.createAnimatedComponent(LinearGradient);

const ICONS: Record<string, string> = {
  Dashboard: 'home',
  Attendance: 'time',
  Leaves: 'calendar-outline',
  Tasks: 'checkbox-outline',
  More: 'grid',
};

function TabButton({
  focused,
  label,
  onPress,
  color,
  activeColor,
  gradient,
}: {
  focused: boolean;
  label: string;
  onPress: () => void;
  color: string;
  activeColor: string;
  gradient: readonly [string, string];
}) {
  const iconStyle = useAnimatedStyle(() => ({
    transform: [{ scale: withSpring(focused ? 1.1 : 1, { damping: 14, stiffness: 220 }) }],
  }));

  const pillStyle = useAnimatedStyle(() => ({
    opacity: withTiming(focused ? 1 : 0, { duration: 200 }),
    transform: [{ scale: withSpring(focused ? 1 : 0.7, { damping: 16, stiffness: 240 }) }],
  }));

  const labelStyle = useAnimatedStyle(() => ({
    opacity: withTiming(focused ? 1 : 0.65, { duration: 180 }),
  }));

  return (
    <Pressable onPress={onPress} style={styles.button} hitSlop={8} android_ripple={{ color: activeColor + '22', borderless: true }}>
      <View style={styles.iconWrap} pointerEvents="none">
        <AnimatedGradient colors={gradient} style={[styles.pill, pillStyle]} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} />
        <Animated.View style={iconStyle}>
          <Icon name={ICONS[label] ?? 'ellipse'} weight={focused ? 'fill' : 'regular'} size={21} color={focused ? '#FFFFFF' : color} />
        </Animated.View>
      </View>
      <Animated.Text
        style={[styles.label, labelStyle, { color: focused ? activeColor : color, fontWeight: focused ? '600' : '500' }]}
      >
        {label}
      </Animated.Text>
    </Pressable>
  );
}

/**
 * Floating pill tab bar — active icon rides a soft accent-tinted capsule
 * that scales in on focus, over a raised, edge-lit surface.
 */
export function AnimatedTabBar({ state, descriptors, navigation }: BottomTabBarProps) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.wrap, { paddingBottom: insets.bottom, backgroundColor: theme.surfaceRaised, borderTopColor: theme.border }]}>
      <View
        style={[
          styles.bar,
          Platform.select({
            ios: { shadowColor: '#08071A', shadowOffset: { width: 0, height: -4 }, shadowOpacity: 0.12, shadowRadius: 12 },
            android: { elevation: 8 },
          }),
        ]}
      >
        {state.routes.map((route, index) => {
          const { options } = descriptors[route.key];
          const label = (options.title ?? route.name) as string;
          const focused = state.index === index;

          const onPress = () => {
            const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
            if (!focused && !event.defaultPrevented) {
              navigation.navigate(route.name);
            }
          };

          return (
            <TabButton
              key={route.key}
              focused={focused}
              label={label}
              onPress={onPress}
              color={theme.textMuted}
              activeColor={theme.primary}
              gradient={theme.gradients.accent}
            />
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { borderTopWidth: StyleSheet.hairlineWidth },
  bar: {
    flexDirection: 'row',
    paddingTop: 4,
    paddingBottom: 4,
  },
  button: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 3 },
  iconWrap: { width: 40, height: 30, alignItems: 'center', justifyContent: 'center' },
  pill: { position: 'absolute', width: 40, height: 30, borderRadius: radii.pill },
  label: { fontSize: 11 },
});