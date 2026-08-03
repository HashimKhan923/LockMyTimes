import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { TaskListScreen } from '../screens/tasks/TaskListScreen';
import { TaskDetailScreen } from '../screens/tasks/TaskDetailScreen';
import { TaskBoardScreen } from '../screens/tasks/TaskBoardScreen';
import { ProjectDetailScreen } from '../screens/projects/ProjectDetailScreen';

export type TasksStackParamList = {
  TaskList: undefined;
  TaskDetail: { id: number };
  ProjectDetail: { id: number };
  TaskBoard: { id: number };
};

const Stack = createNativeStackNavigator<TasksStackParamList>();

export function TasksStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="TaskList" component={TaskListScreen} />
      <Stack.Screen name="TaskDetail" component={TaskDetailScreen} />
      <Stack.Screen name="ProjectDetail" component={ProjectDetailScreen} />
      <Stack.Screen name="TaskBoard" component={TaskBoardScreen} />
    </Stack.Navigator>
  );
}
