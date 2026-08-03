import type { TaskPriority } from '../../api/types';

/**
 * Task type → accent color + icon, mirrored from the web Kanban board's
 * badge-* classes (resources/views/employee/projects/board.blade.php) so
 * the mobile app reads the same visual language.
 */
export const TASK_TYPE_META: Record<string, { color: string; icon: string; label: string }> = {
  bug: { color: '#DC2626', icon: 'bug', label: 'Bug' },
  feature: { color: '#059669', icon: 'star', label: 'Feature' },
  epic: { color: '#7C3AED', icon: 'flag', label: 'Epic' },
  story: { color: '#D97706', icon: 'book-open', label: 'Story' },
  improvement: { color: '#2563EB', icon: 'wrench', label: 'Improvement' },
  support: { color: '#16A34A', icon: 'lifebuoy', label: 'Support' },
  task: { color: '#64748B', icon: 'clipboard-text', label: 'Task' },
};

export function taskTypeMeta(type: string) {
  return TASK_TYPE_META[type] ?? TASK_TYPE_META.task;
}

export const PRIORITY_META: Record<TaskPriority, { colorKey: 'textMuted' | 'primary' | 'warning' | 'danger'; label: string }> = {
  low: { colorKey: 'textMuted', label: 'Low' },
  normal: { colorKey: 'primary', label: 'Normal' },
  high: { colorKey: 'warning', label: 'High' },
  urgent: { colorKey: 'danger', label: 'Urgent' },
};
