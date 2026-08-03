import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { TeamHomeScreen } from '../screens/team/TeamHomeScreen';
import { TeamMemberDetailScreen } from '../screens/team/TeamMemberDetailScreen';
import { LeaveApprovalsScreen } from '../screens/team/LeaveApprovalsScreen';
import { ExpenseApprovalsScreen } from '../screens/team/ExpenseApprovalsScreen';

export type TeamStackParamList = {
  TeamHome: undefined;
  TeamMemberDetail: { id: number };
  LeaveApprovals: undefined;
  ExpenseApprovals: undefined;
};

const Stack = createNativeStackNavigator<TeamStackParamList>();

export function TeamStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="TeamHome" component={TeamHomeScreen} />
      <Stack.Screen name="TeamMemberDetail" component={TeamMemberDetailScreen} />
      <Stack.Screen name="LeaveApprovals" component={LeaveApprovalsScreen} />
      <Stack.Screen name="ExpenseApprovals" component={ExpenseApprovalsScreen} />
    </Stack.Navigator>
  );
}
