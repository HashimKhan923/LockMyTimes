@extends('layouts.superadmin')
@section('title','Notifications')
@section('page-title','Notifications')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Notifications</h2>
            <p class="text-sm text-ink-soft mt-0.5">{{ $notifications->total() }} total</p>
        </div>
        @if($notifications->total() > 0)
        <div class="flex items-center gap-2">
            <form action="{{ route('superadmin.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                    Mark all read
                </button>
            </form>
            <form action="{{ route('superadmin.notifications.clear-all') }}" method="POST"
                  onsubmit="return confirm('Clear all notifications?')">
                @csrf @method('DELETE')
                <button type="submit" class="lmt-btn-ghost lmt-btn-sm text-red-500 hover:bg-red-50">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    Clear all
                </button>
            </form>
        </div>
        @endif
    </div>

    @if($notifications->isEmpty())
    <div class="lmt-card flex flex-col items-center py-16 text-center gap-3">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center">
            <i data-lucide="check-circle-2" class="w-7 h-7 text-emerald-500"></i>
        </div>
        <p class="font-bold text-ink">All clear!</p>
        <p class="text-sm text-ink-soft">No alerts at this time.</p>
    </div>
    @else
    <div class="lmt-card p-0 divide-y divide-gray-100">
        @foreach($notifications as $notif)
        <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition-colors
                    {{ $notif->isUnread() ? 'bg-brand-50/30' : '' }}">

            {{-- Icon --}}
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                 style="background:{{ $notif->color ?? '#6C7DF7' }}20; color:{{ $notif->color ?? '#6C7DF7' }}">
                <i data-lucide="{{ $notif->icon ?? 'bell' }}" class="w-4 h-4"></i>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-ink leading-snug">
                    {{ $notif->title }}
                </p>
                <p class="text-xs text-ink-soft mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                @if($notif->isUnread())
                <form action="{{ route('superadmin.notifications.read', $notif->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" title="Mark as read"
                            class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center transition-colors text-gray-800">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
                @endif
                @if($notif->action_url)
                <a href="{{ $notif->action_url }}"
                   class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center transition-colors text-gray-800">
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                @endif
                <form action="{{ route('superadmin.notifications.destroy', $notif->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete"
                            class="w-7 h-7 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors text-gray-800 hover:text-red-400">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>

            {{-- Unread dot --}}
            @if($notif->isUnread())
            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-3 bg-brand-500"></div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush
