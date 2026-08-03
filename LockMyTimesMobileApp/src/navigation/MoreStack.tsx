import type { NavigatorScreenParams } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { MoreMenuScreen } from '../screens/more/MoreMenuScreen';
import { PayslipListScreen } from '../screens/payslips/PayslipListScreen';
import { PayslipDetailScreen } from '../screens/payslips/PayslipDetailScreen';
import { ProfileScreen } from '../screens/profile/ProfileScreen';
import { EmergencyContactsScreen } from '../screens/profile/EmergencyContactsScreen';
import { ChangePasswordScreen } from '../screens/auth/ChangePasswordScreen';
import { SettingsScreen } from '../screens/settings/SettingsScreen';
import { NotificationsScreen } from '../screens/notifications/NotificationsScreen';
import { ExpenseListScreen } from '../screens/expenses/ExpenseListScreen';
import { ExpenseCreateScreen } from '../screens/expenses/ExpenseCreateScreen';
import { ExpenseDetailScreen } from '../screens/expenses/ExpenseDetailScreen';
import { LoanListScreen } from '../screens/loans/LoanListScreen';
import { LoanApplyScreen } from '../screens/loans/LoanApplyScreen';
import { AdvanceApplyScreen } from '../screens/loans/AdvanceApplyScreen';
import { LoanDetailScreen } from '../screens/loans/LoanDetailScreen';
import { AdvanceDetailScreen } from '../screens/loans/AdvanceDetailScreen';
import { TeamStack } from './TeamStack';
import { AnnouncementsStack, type AnnouncementsStackParamList } from './AnnouncementsStack';

export type MoreStackParamList = {
  MoreMenu: undefined;
  PayslipList: undefined;
  PayslipDetail: { id: number };
  Profile: undefined;
  EmergencyContacts: undefined;
  ChangePassword: undefined;
  Settings: undefined;
  Notifications: undefined;
  ExpenseList: undefined;
  ExpenseCreate: undefined;
  ExpenseDetail: { id: number };
  LoanList: undefined;
  LoanApply: undefined;
  AdvanceApply: undefined;
  LoanDetail: { id: number };
  AdvanceDetail: { id: number };
  Team: undefined;
  Announcements: NavigatorScreenParams<AnnouncementsStackParamList>;
};

const Stack = createNativeStackNavigator<MoreStackParamList>();

/**
 * Grouped menu for feature areas that don't get their own bottom tab (see
 * build plan B2). Team and Announcements are nested stacks — Team's menu
 * entry only shows for managers (see MoreMenuScreen).
 */
export function MoreStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="MoreMenu" component={MoreMenuScreen} />
      <Stack.Screen name="PayslipList" component={PayslipListScreen} />
      <Stack.Screen name="PayslipDetail" component={PayslipDetailScreen} />
      <Stack.Screen name="Profile" component={ProfileScreen} />
      <Stack.Screen name="EmergencyContacts" component={EmergencyContactsScreen} />
      <Stack.Screen name="ChangePassword" component={ChangePasswordScreen} options={{ presentation: 'modal' }} />
      <Stack.Screen name="Settings" component={SettingsScreen} />
      <Stack.Screen name="Notifications" component={NotificationsScreen} />
      <Stack.Screen name="ExpenseList" component={ExpenseListScreen} />
      <Stack.Screen name="ExpenseCreate" component={ExpenseCreateScreen} options={{ presentation: 'modal' }} />
      <Stack.Screen name="ExpenseDetail" component={ExpenseDetailScreen} />
      <Stack.Screen name="LoanList" component={LoanListScreen} />
      <Stack.Screen name="LoanApply" component={LoanApplyScreen} options={{ presentation: 'modal' }} />
      <Stack.Screen name="AdvanceApply" component={AdvanceApplyScreen} options={{ presentation: 'modal' }} />
      <Stack.Screen name="LoanDetail" component={LoanDetailScreen} />
      <Stack.Screen name="AdvanceDetail" component={AdvanceDetailScreen} />
      <Stack.Screen name="Team" component={TeamStack} />
      <Stack.Screen name="Announcements" component={AnnouncementsStack} />
    </Stack.Navigator>
  );
}
