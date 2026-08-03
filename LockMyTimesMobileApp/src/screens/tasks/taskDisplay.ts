import type { TaskPriority } from '../../api/types';

/**
 * Task type → accent color + icon, mirrored from the web Kanban board's
 * badge-* classes (resources/views/employee/projects/board.blade.php) for
 * icon and label — but kept within the app's blue/indigo theme family rather
 * than matching the web's per-type hues (red/green/purple/amber), per the
 * app-wide rule that only genuine status gets its own color. The icon shape
 * and label already distinguish bug vs. feature vs. epic, etc.
 */
export const TASK_TYPE_META: Record<string, { color: string; icon: string; label: string }> = {
  bug: { color: '#3845C2', icon: 'bug', label: 'Bug' },
  feature: { color: '#3D8BFF', icon: 'star', label: 'Feature' },
  epic: { color: '#4A5BE8', icon: 'flag', label: 'Epic' },
  story: { color: '#7989F8', icon: 'book-open', label: 'Story' },
  improvement: { color: '#2563EB', icon: 'wrench', label: 'Improvement' },
  support: { color: '#5FC6FF', icon: 'lifebuoy', label: 'Support' },
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
