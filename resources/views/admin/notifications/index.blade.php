@extends('layouts.admin')
@section('title','Notifications')
@section('page-title','Notifications')

@section('content')
<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-black text-gray-900 dark:text-slate-100">Notifications</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $notifications->total() }} total</p>
        </div>
        @if($notifications->total() > 0)
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.notifications.read-all', $tenant) }}" method="POST">
                @csrf
                <button type="submit" class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                    Mark all read
                </button>
            </form>
            <form action="{{ route('admin.notifications.clear-all', $tenant) }}" method="POST"
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
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
            <i data-lucide="bell-off" class="w-7 h-7 text-gray-400"></i>
        </div>
        <p class="font-bold text-gray-900 dark:text-slate-100">No notifications</p>
        <p class="text-sm text-gray-400">You're all caught up!</p>
    </div>
    @else
    <div class="lmt-card p-0 divide-y divide-gray-100 dark:divide-slate-700">
        @foreach($notifications as $notif)
        <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors
                    {{ $notif->isUnread() ? 'bg-blue-50/30 dark:bg-slate-700/30' : '' }}">

            {{-- Icon --}}
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                 style="background:{{ $notif->color ?? 'var(--brand-500)' }}20; color:{{ $notif->color ?? 'var(--brand-500)' }}">
                <i data-lucide="{{ $notif->icon ?? 'bell' }}" class="w-4 h-4"></i>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 leading-snug">
                    {{ $notif->title }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                @if($notif->isUnread())
                <form action="{{ route('admin.notifications.read', [$tenant, $notif->id]) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" title="Mark as read"
                            class="w-7 h-7 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 flex items-center justify-center transition-colors text-gray-400">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
                @endif
                @if($notif->action_url)
                <a href="{{ $notif->action_url }}"
                   class="w-7 h-7 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 flex items-center justify-center transition-colors text-gray-400">
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                @endif
                <form action="{{ route('admin.notifications.destroy', [$tenant, $notif->id]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete"
                            class="w-7 h-7 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors text-gray-300 hover:text-red-400">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>

            {{-- Unread dot --}}
            @if($notif->isUnread())
            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-3" style="background:var(--brand-500);"></div>
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
