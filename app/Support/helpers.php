<?php

if (! function_exists('format_hours')) {
    /**
     * Convert a decimal hours value (e.g. 1.62) into a human-readable "Xh Ym" string
     * (e.g. "1h 37m") instead of a raw decimal that's hard to read at a glance.
     *
     * @param  float|int|string|null  $hours
     * @param  string  $empty  What to return for null/zero/negative input.
     */
    function format_hours($hours, string $empty = '—'): string
    {
        $hours = (float) $hours;

        if ($hours <= 0) {
            return $empty;
        }

        $totalMinutes = (int) round($hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0)           return "{$h}h";
        return "{$m}m";
    }
}
