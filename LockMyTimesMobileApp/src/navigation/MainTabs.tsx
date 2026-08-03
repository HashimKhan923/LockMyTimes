import type { NavigatorScreenParams } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { DashboardScreen } from '../screens/dashboard/DashboardScreen';
import { AttendanceStack, type AttendanceStackParamList } from './AttendanceStack';
import { LeavesStack, type LeavesStackParamList } from './LeavesStack';
import { TasksStack, type TasksStackParamList } from './TasksStack';
import { MoreStack, type MoreStackParamList } from './MoreStack';
import { AnimatedTabBar } from './AnimatedTabBar';

export type MainTabsParamList = {
  Dashboard: undefined;
  Attendance: NavigatorScreenParams<AttendanceStackParamList>;
  Leaves: NavigatorScreenParams<LeavesStackParamList>;
  Tasks: NavigatorScreenParams<TasksStackParamList>;
  More: NavigatorScreenParams<MoreStackParamList>;
};

const Tab = createBottomTabNavigator<MainTabsParamList>();

export function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={{ headerShown: false }}
      tabBar={(props) => <AnimatedTabBar {...props} />}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Attendance" component={AttendanceStack} />
      <Tab.Screen name="Leaves" component={LeavesStack} />
      <Tab.Screen name="Tasks" component={TasksStack} />
      <Tab.Screen name="More" component={MoreStack} />
    </Tab.Navigator>
  );
}
