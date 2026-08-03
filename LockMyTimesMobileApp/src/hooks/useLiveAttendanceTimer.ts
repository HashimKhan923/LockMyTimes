import { useEffect, useRef, useState } from 'react';
import type { ClockStatus } from '../api/types';

export interface LiveAttendanceTimer {
  /** Ticks up once per second while clocked in; frozen during a break. */
  workedSeconds: number;
  /** Ticks down once per second while clocked in or on break; null when not applicable. Goes negative once overtime starts. */
  remainingSeconds: number | null;
  /** Ticks up once per second while on a break; 0 otherwise. */
  breakSeconds: number;
  isOvertime: boolean;
}

/**
 * Ticks worked/remaining/break durations locally every second, seeded from
 * the last server snapshot (worked_minutes, refetched periodically by the
 * attendance status poll) rather than re-fetching every second. Worked time
 * freezes during a break — matching the server's own calculation, which
 * already excludes break time — so it doesn't jump when the poll catches up.
 * Remaining time counts down against today's shift end when one is assigned,
 * otherwise against a flat daily-goal fallback.
 */
export function useLiveAttendanceTimer({
  status,
  workedMinutes,
  breakStartedAt,
  shiftEndIso,
  dailyGoalMinutes = 8 * 60,
}: {
  status: ClockStatus;
  workedMinutes: number;
  breakStartedAt: string | null;
  shiftEndIso: string | null;
  dailyGoalMinutes?: number;
}): LiveAttendanceTimer {
  const [, forceTick] = useState(0);
  const baseline = useRef({ workedMinutes, capturedAt: Date.now() });

  // Re-baseline whenever a fresh server value comes in (each status poll,
  // and immediately after clocking in/out or starting/ending a break).
  useEffect(() => {
    baseline.current = { workedMinutes, capturedAt: Date.now() };
  }, [workedMinutes]);

  useEffect(() => {
    const id = setInterval(() => forceTick((t) => t + 1), 1000);
    return () => clearInterval(id);
  }, []);

  const elapsedSinceBaseline = status === 'clocked_in' ? (Date.now() - baseline.current.capturedAt) / 1000 : 0;
  const workedSeconds = Math.max(0, Math.floor(baseline.current.workedMinutes * 60 + elapsedSinceBaseline));

  let remainingSeconds: number | null = null;
  if (status === 'clocked_in' || status === 'on_break') {
    remainingSeconds = shiftEndIso
      ? Math.floor((new Date(shiftEndIso).getTime() - Date.now()) / 1000)
      : dailyGoalMinutes * 60 - workedSeconds;
  }

  const breakSeconds =
    status === 'on_break' && breakStartedAt
      ? Math.max(0, Math.floor((Date.now() - new Date(breakStartedAt).getTime()) / 1000))
      : 0;

  return {
    workedSeconds,
    remainingSeconds,
    breakSeconds,
    isOvertime: remainingSeconds !== null && remainingSeconds < 0,
  };
}
