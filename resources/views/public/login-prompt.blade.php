@extends('layouts.public')
@section('title', 'Sign In')

@section('content')
<section class="min-h-screen flex items-center justify-center lmt-mesh-bg px-6 py-24">
    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="flex items-center justify-center gap-2.5 mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-9 h-9 object-contain"/>
            <span class="text-xl font-bold text-brand-500" style="font-family:'Syne',sans-serif">Lockmytimes</span>
        </a>

        <div class="bg-white rounded-2xl shadow-soft border border-gray-100 p-8">
            <h1 class="text-xl font-black text-ink mb-1">
                {{ $type === 'admin' ? 'Admin Sign In' : 'Employee Sign In' }}
            </h1>
            <p class="text-sm text-ink-soft mb-6">
                Enter your company's workspace address to continue — we'll remember it next time.
            </p>

            @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-600 text-sm">{{ session('error') }}</div>
            @endif

            <form action="{{ route('login.resolve') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="{{ $type === 'admin' ? 'admin' : 'portal' }}"/>
                <div>
                    <label class="text-xs font-bold text-ink-soft uppercase tracking-wider">Company Workspace</label>
                    <div class="mt-1.5 flex items-center rounded-xl border border-gray-200 focus-within:border-brand-400 transition-colors overflow-hidden">
                        <span class="pl-4 text-sm text-gray-800 whitespace-nowrap">lockmytimes.com/t/</span>
                        <input type="text" name="slug" value="{{ old('slug') }}" required autofocus
                               placeholder="yourcompany"
                               class="flex-1 min-w-0 py-3 pr-4 text-sm outline-none"/>
                    </div>
                    @error('slug')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="lmt-btn-primary w-full justify-center">Continue</button>
            </form>

            <p class="text-xs text-ink-soft text-center mt-6">
                Don't know your company's workspace address? Ask your HR admin.
            </p>
        </div>
    </div>
</section>
@endsection
