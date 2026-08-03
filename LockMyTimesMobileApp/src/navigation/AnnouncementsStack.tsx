import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AnnouncementListScreen } from '../screens/announcements/AnnouncementListScreen';
import { AnnouncementDetailScreen } from '../screens/announcements/AnnouncementDetailScreen';
import { PollScreen } from '../screens/announcements/PollScreen';

export type AnnouncementsStackParamList = {
  AnnouncementList: undefined;
  AnnouncementDetail: { id: number };
  Poll: { id: number };
};

const Stack = createNativeStackNavigator<AnnouncementsStackParamList>();

export function AnnouncementsStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="AnnouncementList" component={AnnouncementListScreen} />
      <Stack.Screen name="AnnouncementDetail" component={AnnouncementDetailScreen} />
      <Stack.Screen name="Poll" component={PollScreen} />
    </Stack.Navigator>
  );
}
