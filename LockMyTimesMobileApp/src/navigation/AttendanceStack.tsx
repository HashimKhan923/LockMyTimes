import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AttendanceHistoryScreen } from '../screens/attendance/AttendanceHistoryScreen';
import { AttendanceHomeScreen } from '../screens/attendance/AttendanceHomeScreen';
import { ClockInScreen } from '../screens/attendance/ClockInScreen';
import { DayDetailScreen } from '../screens/attendance/DayDetailScreen';
import { ClockSuccessScreen } from '../screens/attendance/ClockSuccessScreen';

export type AttendanceStackParamList = {
  AttendanceHome: undefined;
  History: undefined;
  ClockIn: { mode: 'in' | 'out' };
  DayDetail: { date: string };
  Success: { message: string };
};

const Stack = createNativeStackNavigator<AttendanceStackParamList>();

export function AttendanceStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="AttendanceHome" component={AttendanceHomeScreen} />
      <Stack.Screen name="History" component={AttendanceHistoryScreen} />
      <Stack.Screen name="DayDetail" component={DayDetailScreen} />
      <Stack.Screen
        name="ClockIn"
        component={ClockInScreen}
        options={{ presentation: 'formSheet' }}
      />
      <Stack.Screen
        name="Success"
        component={ClockSuccessScreen}
        options={{ presentation: 'transparentModal', animation: 'fade' }}
      />
    </Stack.Navigator>
  );
}
