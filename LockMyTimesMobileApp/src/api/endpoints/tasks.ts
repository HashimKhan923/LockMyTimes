import { apiClient } from '../client';
import type { ProjectBoardResponse, TaskInfo, TaskShowResponse, TasksIndexResponse } from '../types';

export interface TasksFilter {
  filter?: string;
  priority?: string;
  project?: number;
  q?: string;
}

export async function fetchTasks(filters: TasksFilter = {}): Promise<TasksIndexResponse> {
  const { data } = await apiClient.get<TasksIndexResponse>('/tasks', { params: filters });
  return data;
}

export async function fetchTask(id: number): Promise<TaskShowResponse> {
  const { data } = await apiClient.get<TaskShowResponse>(`/tasks/${id}`);
  return data;
}

export async function fetchProjectBoard(projectId: number): Promise<ProjectBoardResponse> {
  const { data } = await apiClient.get<ProjectBoardResponse>(`/projects/${projectId}/board`);
  return data;
}

export async function moveTask(
  projectId: number,
  taskId: number,
  taskListId: number
): Promise<{ success: true; status: string }> {
  const { data } = await apiClient.post(`/projects/${projectId}/tasks/${taskId}/move`, {
    task_list_id: taskListId,
  });
  return data;
}

export async function updateTaskStatus(
  taskId: number,
  status: string
): Promise<{ success: true; task: TaskInfo; message: string }> {
  const { data } = await apiClient.put(`/tasks/${taskId}/status`, { status });
  return data;
}

export async function updateTaskProgress(
  taskId: number,
  progress: number
): Promise<{ success: true; task: TaskInfo; message: string }> {
  const { data } = await apiClient.put(`/tasks/${taskId}/progress`, { progress });
  return data;
}

export async function addTaskComment(
  taskId: number,
  content: string
): Promise<{ success: true; comment: TaskInfo }> {
  const { data } = await apiClient.post(`/tasks/${taskId}/comments`, { content });
  return data;
}

export async function addTaskChecklistItem(
  taskId: number,
  item: string
): Promise<{ success: true; checklist: TaskInfo }> {
  const { data } = await apiClient.post(`/tasks/${taskId}/checklists`, { item });
  return data;
}

export async function toggleTaskChecklistItem(
  taskId: number,
  checklistId: number
): Promise<{ success: true; is_completed: boolean }> {
  const { data } = await apiClient.patch(`/tasks/${taskId}/checklists/${checklistId}/toggle`);
  return data;
}
