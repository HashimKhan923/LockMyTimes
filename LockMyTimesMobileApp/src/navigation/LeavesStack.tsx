import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { LeaveListScreen } from '../screens/leaves/LeaveListScreen';
import { LeaveApplyScreen } from '../screens/leaves/LeaveApplyScreen';
import { LeaveDetailScreen } from '../screens/leaves/LeaveDetailScreen';

export type LeavesStackParamList = {
  LeaveList: undefined;
  LeaveApply: undefined;
  LeaveDetail: { id: number };
};

const Stack = createNativeStackNavigator<LeavesStackParamList>();

export function LeavesStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="LeaveList" component={LeaveListScreen} />
      <Stack.Screen name="LeaveApply" component={LeaveApplyScreen} options={{ presentation: 'modal' }} />
      <Stack.Screen name="LeaveDetail" component={LeaveDetailScreen} />
    </Stack.Navigator>
  );
}
