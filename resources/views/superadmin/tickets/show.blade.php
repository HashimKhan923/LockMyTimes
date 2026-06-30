@extends('layouts.superadmin')
@section('title','Ticket — '.$ticket->ticket_number)
@section('page-title','Support Ticket')

@section('content')

@php
$priorityMap = ['urgent'=>'lmt-badge-red','high'=>'lmt-badge-amber','normal'=>'lmt-badge-brand','low'=>'lmt-badge-gray'];
$statusMap   = ['open'=>'lmt-badge-brand','in_progress'=>'lmt-badge-amber','waiting_on_customer'=>'lmt-badge-gray','resolved'=>'lmt-badge-green','closed'=>'lmt-badge-gray'];
@endphp

{{-- Back + header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('superadmin.tickets.index') }}"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 text-ink-soft"></i>
    </a>
    <div class="flex-1">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">{{ $ticket->subject }}</h2>
            <span class="{{ $priorityMap[$ticket->priority] ?? 'lmt-badge-gray' }} text-xs capitalize">{{ $ticket->priority }}</span>
            <span class="{{ $statusMap[$ticket->status] ?? 'lmt-badge-gray' }} text-xs">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
        </div>
        <p class="text-sm text-ink-soft mt-0.5 font-mono">{{ $ticket->ticket_number }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Conversation thread --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Original message --}}
        <div class="lmt-card">
            <div class="flex items-start gap-4">
                <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">
                    {{ substr($ticket->tenant?->company_name ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <p class="font-bold text-ink text-sm">{{ $ticket->tenant?->company_name ?? 'Unknown' }}</p>
                        <span class="text-xs text-ink-soft">{{ $ticket->created_at->format('M j, Y H:i') }}</span>
                        <span class="lmt-badge-gray text-xs capitalize">{{ str_replace('_',' ',$ticket->category) }}</span>
                    </div>
                    <p class="text-sm text-ink leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                </div>
            </div>
        </div>

        {{-- Replies --}}
        @foreach($ticket->replies as $reply)
        @php $isAgent = str_contains(strtolower($reply->author_type ?? ''), 'superadmin'); @endphp
        <div class="lmt-card {{ $reply->is_internal_note ? 'border-l-4 border-amber-400 bg-amber-50/30' : ($isAgent ? 'bg-brand-50/20' : '') }}">
            <div class="flex items-start gap-4">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                            {{ $isAgent ? '' : 'bg-gray-200 text-gray-600' }}"
                     style="{{ $isAgent ? 'background:linear-gradient(135deg,#6C7DF7,#4A5BE8);' : '' }}">
                    {{ substr($reply->author?->name ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1 flex-wrap">
                        <p class="font-bold text-ink text-sm">{{ $reply->author?->name ?? 'Unknown' }}</p>
                        @if($isAgent)
                        <span class="lmt-badge-brand text-xs">Support Agent</span>
                        @endif
                        @if($reply->is_internal_note)
                        <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Internal Note</span>
                        @endif
                        <span class="text-xs text-ink-soft">{{ $reply->created_at->format('M j, Y H:i') }}</span>
                    </div>
                    <p class="text-sm text-ink leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                </div>
            </div>
        </div>
        @endforeach

        {{-- Reply form --}}
        @if(!in_array($ticket->status, ['closed']))
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Send Reply</h3>
            <form action="{{ route('superadmin.tickets.reply', $ticket) }}" method="POST">
                @csrf
                <textarea name="message" rows="5" required
                          class="lmt-textarea mb-4"
                          placeholder="Type your response here…">{{ old('message') }}</textarea>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_internal_note" value="0">
                        <input type="checkbox" name="is_internal_note" value="1"
                               class="w-4 h-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400"
                               {{ old('is_internal_note') ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-ink-soft">Internal note (not visible to customer)</span>
                    </label>
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- Sidebar: Ticket details --}}
    <div class="space-y-5">

        {{-- Status update --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Update Status</h3>
            <form action="{{ route('superadmin.tickets.status', $ticket) }}" method="POST">
                @csrf @method('PATCH')
                <select name="status" class="lmt-select mb-3">
                    @foreach(['open','in_progress','waiting_on_customer','resolved','closed'] as $s)
                    <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_',' ',$s)) }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="lmt-btn-secondary w-full justify-center">
                    Update Status
                </button>
            </form>
        </div>

        {{-- Assign --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Assigned To</h3>
            <form action="{{ route('superadmin.tickets.assign', $ticket) }}" method="POST">
                @csrf @method('PATCH')
                <select name="assigned_to" class="lmt-select mb-3">
                    <option value="">Unassigned</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                        {{ $agent->name }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="lmt-btn-secondary w-full justify-center">
                    Update Assignment
                </button>
            </form>
        </div>

        {{-- Details --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Ticket Details</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Company</dt>
                    <dd class="font-semibold text-ink text-right">{{ $ticket->tenant?->company_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Contact</dt>
                    <dd class="font-semibold text-ink text-right text-xs">{{ $ticket->tenant?->contact_email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Priority</dt>
                    <dd><span class="{{ $priorityMap[$ticket->priority] ?? 'lmt-badge-gray' }} text-xs capitalize">{{ $ticket->priority }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Category</dt>
                    <dd class="font-semibold text-ink capitalize">{{ str_replace('_',' ',$ticket->category) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Replies</dt>
                    <dd class="font-semibold text-ink">{{ $ticket->replies->count() }}</dd>
                </div>
                <div class="pt-2 border-t border-gray-100 space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">Created</dt>
                        <dd class="text-ink-soft text-xs">{{ $ticket->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                    @if($ticket->first_response_at)
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">First response</dt>
                        <dd class="text-ink-soft text-xs">{{ $ticket->first_response_at->format('M j, Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($ticket->resolved_at)
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">Resolved</dt>
                        <dd class="text-emerald-600 text-xs font-semibold">{{ $ticket->resolved_at->format('M j, Y H:i') }}</dd>
                    </div>
                    @endif
                </div>
            </dl>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
