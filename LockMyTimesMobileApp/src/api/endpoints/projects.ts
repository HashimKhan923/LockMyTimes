import { apiClient } from '../client';
import type { ProjectInfo } from '../types';

export interface ProjectsIndexResponse {
  projects: ProjectInfo[];
  pagination: { current_page: number; last_page: number; total: number };
  counters: { total: number; active: number; planning: number; on_hold: number; completed: number };
}

export async function fetchProjects(status = 'active'): Promise<ProjectsIndexResponse> {
  const { data } = await apiClient.get<ProjectsIndexResponse>('/projects', { params: { status } });
  return data;
}
