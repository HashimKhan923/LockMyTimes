<div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

    @php
        // Show only cells that fall inside this month, sorted ascending (1st -> end of month)
        $rows = collect($calendarCells)
            ->filter(fn ($c) => $c->in_month)
            ->sortBy(fn ($c) => $c->date->toDateString())
            ->values();
    @endphp

    @if($rows->isEmpty())
        <div class="text-center py-16">
            <i data-lucide="calendar-x" class="w-10 h-10 mx-auto text-gray-800 mb-3"></i>
            <p class="text-sm text-gray-800">No records for this month.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Clock In</th>
                        <th class="text-left">Clock Out</th>
                        <th class="text-left">Hours</th>
                        <th class="text-left hidden lg:table-cell">Overtime</th>
                        <th class="text-left hidden lg:table-cell">Break</th>
                        <th class="text-left hidden md:table-cell">Location</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $cell)
                        @php
                            $d   = $cell->date;
                            $att = $cell->attendance;
                            $hol = $cell->holiday;
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                            <td>
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ $d->format('M j') }}</div>
                                <div class="text-xs text-gray-800">{{ $d->format('D') }}</div>
                            </td>

                            <td>
                                @if($att)
                                    @php
                                        [$lbl, $cls] = match($att->status) {
                                            'present'  => $att->is_late ? ['Late', 'lmt-badge-amber'] : ['Present', 'lmt-badge-green'],
                                            'absent'   => ['Absent',   'lmt-badge-red'],
                                            'on_leave' => ['On Leave', 'lmt-badge-brand'],
                                            'half_day' => ['Half Day', 'lmt-badge-amber'],
                                            'holiday'  => ['Holiday',  'lmt-badge-brand'],
                                            default    => [ucfirst(str_replace('_', ' ', $att->status)), 'lmt-badge-gray'],
                                        };
                                    @endphp
                                    <span class="{{ $cls }}">{{ $lbl }}</span>
                                @elseif($hol)
                                    <span class="lmt-badge-brand">{{ $hol->name }}</span>
                                @elseif($d->isWeekend())
                                    <span class="lmt-badge-gray">Weekend</span>
                                @elseif($d->isFuture())
                                    <span class="lmt-badge-gray">—</span>
                                @else
                                    <span class="lmt-badge-gray">No record</span>
                                @endif
                            </td>

                            <td class="font-mono text-sm">
                                {{ $att?->clock_in_at ? \Carbon\Carbon::parse($att->clock_in_at)->format('h:i A') : '—' }}
                            </td>
                            <td class="font-mono text-sm">
                                {{ $att?->clock_out_at ? \Carbon\Carbon::parse($att->clock_out_at)->format('h:i A') : '—' }}
                            </td>
                            <td class="font-mono text-sm font-bold">
                                {{ $att && $att->total_hours ? number_format((float) $att->total_hours, 2).'h' : '—' }}
                            </td>
                            <td class="hidden lg:table-cell font-mono text-sm">
                                @if($att?->overtime_hours)
                                    <span class="text-emerald-600 font-bold">+{{ number_format((float) $att->overtime_hours, 2) }}h</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="hidden lg:table-cell font-mono text-sm">
                                {{ $att?->break_hours ? number_format((float) $att->break_hours, 2).'h' : '—' }}
                            </td>
                            <td class="hidden md:table-cell text-sm text-gray-800">
                                {{ $att?->location?->name ?? '—' }}
                            </td>
                            <td class="text-right">
                                <button @click="openDay(@js($d->toDateString()))"
                                        class="text-xs font-bold transition-colors hover:underline" style="color:var(--brand-500);">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>