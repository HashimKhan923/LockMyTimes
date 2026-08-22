@extends('layouts.superadmin')
@section('title','Settings')
@section('page-title','Settings')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Settings</h2>
        <p class="text-sm text-ink-soft mt-0.5">Manage your account and platform configuration.</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left column --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Profile --}}
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">My Profile</h3>
                    <p class="text-xs text-ink-soft">Update your display name, email and phone.</p>
                </div>
            </div>
            <form action="{{ route('superadmin.settings.profile') }}" method="POST">
                @csrf @method('PUT')
                <div class="grid sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="lmt-label">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $admin->name) }}"
                               class="lmt-input" required>
                        @error('name')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $admin->email) }}"
                               class="lmt-input" required>
                        @error('email')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $admin->phone) }}"
                               class="lmt-input" placeholder="+1 555 000 0000">
                    </div>
                    <div>
                        <label class="lmt-label">Role</label>
                        <input type="text" value="{{ ucfirst($admin->role ?? 'owner') }}"
                               class="lmt-input bg-gray-50 cursor-not-allowed" disabled>
                    </div>
                </div>
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Profile
                </button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Change Password</h3>
                    <p class="text-xs text-ink-soft">Must be at least 8 characters with mixed case and numbers.</p>
                </div>
            </div>
            <form action="{{ route('superadmin.settings.password') }}" method="POST">
                @csrf @method('PUT')
                <div class="grid sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="lmt-label">Current Password</label>
                        <input type="password" name="current_password" class="lmt-input" autocomplete="current-password" required>
                        @error('current_password')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">New Password</label>
                        <input type="password" name="password" class="lmt-input" autocomplete="new-password" required>
                        @error('password')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="lmt-input" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="key" class="w-4 h-4"></i>
                    Change Password
                </button>
            </form>
        </div>

        {{-- Platform info (read-only) --}}
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="settings-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Platform Configuration</h3>
                    <p class="text-xs text-ink-soft">Configure via your <code class="bg-gray-100 px-1 rounded text-xs">.env</code> file.</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">App Name</label>
                    <input type="text" value="{{ $platformSettings['app_name'] }}" class="lmt-input bg-gray-50 cursor-not-allowed" disabled>
                </div>
                <div>
                    <label class="lmt-label">Default Trial Days</label>
                    <input type="text" value="{{ $platformSettings['trial_days'] }}" class="lmt-input bg-gray-50 cursor-not-allowed" disabled>
                </div>
                <div>
                    <label class="lmt-label">Support Email (From)</label>
                    <input type="text" value="{{ $platformSettings['support_email'] }}" class="lmt-input bg-gray-50 cursor-not-allowed" disabled>
                </div>
                <div>
                    <label class="lmt-label">Environment</label>
                    <input type="text" value="{{ app()->environment() }}" class="lmt-input bg-gray-50 cursor-not-allowed" disabled>
                </div>
            </div>
            <p class="text-xs text-ink-soft mt-4">
                <i data-lucide="info" class="w-3.5 h-3.5 inline-block mr-1 text-brand-400"></i>
                These values are loaded from <code class="bg-gray-100 px-1 rounded">.env</code>. Update them there and redeploy.
            </p>
        </div>

        {{-- Agent team --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Admin Team</h3>
                        <p class="text-xs text-ink-soft">{{ $agents->count() }} team member(s)</p>
                    </div>
                </div>
                <button onclick="document.getElementById('invite-panel').classList.toggle('hidden')"
                        class="lmt-btn-ghost lmt-btn-sm">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    Invite Agent
                </button>
            </div>

            {{-- Invite form (hidden by default) --}}
            <div id="invite-panel" class="hidden mb-5 p-4 bg-brand-50 rounded-xl border border-brand-100">
                <h4 class="font-semibold text-ink mb-3 text-sm">Invite New Agent</h4>
                <form action="{{ route('superadmin.settings.agents.invite') }}" method="POST">
                    @csrf
                    <div class="grid sm:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="lmt-label text-xs">Name</label>
                            <input type="text" name="name" class="lmt-input text-sm" required>
                        </div>
                        <div>
                            <label class="lmt-label text-xs">Email</label>
                            <input type="email" name="email" class="lmt-input text-sm" required>
                        </div>
                        <div>
                            <label class="lmt-label text-xs">Role</label>
                            <select name="role" class="lmt-select text-sm">
                                <option value="support">Support</option>
                                <option value="analyst">Analyst</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Add Agent
                    </button>
                </form>
            </div>

            {{-- Agent list --}}
            <div class="space-y-3">
                @foreach($agents as $agent)
                <div class="flex items-center gap-4 p-3 rounded-xl {{ !$agent->is_active ? 'bg-gray-50 opacity-60' : 'hover:bg-gray-50' }} transition-colors">
                    <div class="w-10 h-10 rounded-xl text-white text-sm font-bold flex items-center justify-center flex-shrink-0"
                         style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                        {{ substr($agent->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-ink text-sm truncate">{{ $agent->name }}</p>
                        <p class="text-xs text-ink-soft truncate">{{ $agent->email }}</p>
                    </div>
                    <span class="lmt-badge-{{ $agent->role === 'owner' ? 'brand' : 'gray' }} text-xs capitalize">{{ $agent->role }}</span>
                    <span class="{{ $agent->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                        {{ $agent->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($agent->id !== $admin->id)
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <form action="{{ route('superadmin.settings.agents.toggle', $agent) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-7 h-7 rounded-lg {{ $agent->is_active ? 'bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white' }} flex items-center justify-center transition-colors"
                                    title="{{ $agent->is_active ? 'Deactivate' : 'Activate' }}">
                                <i data-lucide="{{ $agent->is_active ? 'pause' : 'play' }}" class="w-3 h-3"></i>
                            </button>
                        </form>
                        <form action="{{ route('superadmin.settings.agents.destroy', $agent) }}" method="POST"
                              onsubmit="return confirm('Remove agent {{ addslashes($agent->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-7 h-7 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                            </button>
                        </form>
                    </div>
                    @else
                    <span class="text-xs text-ink-soft italic px-2">You</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div class="space-y-5">

        {{-- Account summary --}}
        <div class="lmt-card text-center">
            <div class="w-16 h-16 rounded-2xl text-white text-2xl font-black flex items-center justify-center mx-auto mb-4"
                 style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);box-shadow:0 8px 24px rgba(108,125,247,0.4);">
                {{ substr($admin->name, 0, 1) }}
            </div>
            <h3 class="font-black text-ink" style="font-family:'Nunito',sans-serif">{{ $admin->name }}</h3>
            <p class="text-sm text-ink-soft">{{ $admin->email }}</p>
            <span class="mt-2 inline-block lmt-badge-brand text-xs capitalize">{{ $admin->role ?? 'owner' }}</span>
            @if($admin->last_login_at)
            <p class="text-xs text-gray-800 mt-3">Last login: {{ $admin->last_login_at->diffForHumans() }}</p>
            @endif
        </div>

        {{-- System info --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">System Info</h3>
            <dl class="space-y-3 text-sm">
                @foreach([
                    ['label'=>'PHP Version',   'value'=>PHP_VERSION],
                    ['label'=>'Laravel',        'value'=>app()->version()],
                    ['label'=>'Timezone',       'value'=>config('app.timezone')],
                    ['label'=>'Locale',         'value'=>config('app.locale')],
                    ['label'=>'Cache Driver',   'value'=>config('cache.default')],
                    ['label'=>'Queue Driver',   'value'=>config('queue.default')],
                ] as $item)
                <div class="flex items-center justify-between">
                    <dt class="text-ink-soft">{{ $item['label'] }}</dt>
                    <dd class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded text-ink">{{ $item['value'] }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

        {{-- Danger zone --}}
        <div class="lmt-card border-red-100">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i>
                <h3 class="font-bold text-red-600 text-sm" style="font-family:'Nunito',sans-serif">Danger Zone</h3>
            </div>
            <p class="text-xs text-ink-soft mb-4">
                These actions affect the entire platform. Use with extreme caution.
            </p>
            <div class="space-y-2">
                <button onclick="alert('Feature coming soon: Cache clearing')"
                        class="w-full lmt-btn-sm border border-red-200 text-red-600 hover:bg-red-50 rounded-xl py-2 text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                    Clear Application Cache
                </button>
                <button onclick="alert('Feature coming soon: Queue flush')"
                        class="w-full lmt-btn-sm border border-red-200 text-red-600 hover:bg-red-50 rounded-xl py-2 text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    Flush Failed Jobs
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
