@extends('layouts.employee')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div x-data="{
        tab: (window.location.hash || '').replace('#', '') || '{{ $tab }}',
        init() {
            window.addEventListener('hashchange', () => {
                this.tab = (window.location.hash || '').replace('#', '') || 'personal';
            });
        },
        switchTab(t) {
            this.tab = t;
            history.replaceState(null, '', '#' + t);
        }
     }" class="max-w-5xl mx-auto">

    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€', 'GBP' => '£', 'JPY', 'CNY' => '¥',
            'INR' => '₹', 'PKR' => 'Rs',
            default => $cur.' ',
        };
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         PROFILE HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row gap-5 items-start sm:items-center">

            {{-- Avatar with upload trigger --}}
            <div class="relative flex-shrink-0">
                <img src="{{ $emp->avatar_url }}" alt="{{ $emp->full_name }}"
                     class="w-24 h-24 lg:w-28 lg:h-28 rounded-2xl ring-4 ring-white/30 shadow-xl object-cover"/>
                <form action="{{ route('employee.profile.avatar', $tenantSlug) }}" method="POST"
                      enctype="multipart/form-data" class="absolute -bottom-1.5 -right-1.5">
                    @csrf
                    <label class="w-9 h-9 rounded-xl bg-white text-gray-700 hover:bg-gray-50 cursor-pointer flex items-center justify-center shadow-lg transition-all hover:scale-105"
                           title="Change photo">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                               class="hidden"
                               onchange="if(this.files.length){this.form.submit()}"/>
                    </label>
                </form>
            </div>

            {{-- Name + meta --}}
            <div class="flex-1 min-w-0">
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">{{ $emp->employee_code ?? 'Employee' }}</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-0.5" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $emp->full_name }}
                </h1>
                <p class="text-white/85 text-sm mt-1">
                    {{ $emp->position?->title ?? 'No position assigned' }}
                    @if($emp->department) &middot; {{ $emp->department->name }} @endif
                </p>
                <div class="flex flex-wrap gap-3 mt-3 text-xs text-white/80">
                    @if($emp->email)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                            {{ $emp->email }}
                        </span>
                    @endif
                    @if($emp->hire_date)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            Joined {{ $emp->hire_date->format('M Y') }}
                            <span class="text-white/60">({{ $emp->service_months }} mo)</span>
                        </span>
                    @endif
                    @if($emp->location)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                            {{ $emp->location->name }}
                        </span>
                    @endif
                </div>

                @if($emp->avatar)
                    <form action="{{ route('employee.profile.avatar.remove', $tenantSlug) }}" method="POST" class="inline-block mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('Remove your profile photo?')"
                                class="text-[10px] font-bold text-white/70 hover:text-white underline">
                            Remove photo
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="lmt-alert lmt-alert-success mb-5">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         TABS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-1 mb-5 border-b border-gray-200 dark:border-slate-700 overflow-x-auto">
        @php
            $tabs = [
                ['key' => 'personal',   'label' => 'Personal',   'icon' => 'user'],
                ['key' => 'address',    'label' => 'Address',    'icon' => 'map-pin'],
                ['key' => 'emergency',  'label' => 'Emergency',  'icon' => 'phone'],
                ['key' => 'employment', 'label' => 'Employment', 'icon' => 'briefcase'],
                ['key' => 'security',   'label' => 'Security',   'icon' => 'lock'],
            ];
        @endphp
        @foreach($tabs as $t)
            <button @click="switchTab('{{ $t['key'] }}')"
                    :class="tab==='{{ $t['key'] }}' ? 'text-white' : 'text-gray-500 hover:text-gray-700 dark:hover:text-slate-300'"
                    class="px-4 py-2.5 text-sm font-bold rounded-t-xl -mb-px transition-all whitespace-nowrap flex items-center gap-2"
                    :style="tab==='{{ $t['key'] }}' ? 'background:var(--brand-500);' : ''">
                <i data-lucide="{{ $t['icon'] }}" class="w-4 h-4"></i>
                {{ $t['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB 1 — PERSONAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='personal'" x-cloak>
        <form action="{{ route('employee.profile.update', $tenantSlug) }}" method="POST" class="lmt-card">
            @csrf
            @method('PUT')

            <div class="mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Personal Information</h2>
                <p class="text-xs text-gray-500 mt-0.5">Update your personal details. Legal name and email changes must go through HR.</p>
            </div>

            {{-- Read-only legal name --}}
            <div class="grid sm:grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="lmt-label">First name <span class="text-gray-400 text-[10px] uppercase font-bold ml-1">HR-locked</span></label>
                    <input type="text" value="{{ $emp->first_name }}" disabled class="lmt-input bg-gray-50 dark:bg-slate-800 cursor-not-allowed"/>
                </div>
                <div>
                    <label class="lmt-label">Middle name <span class="text-gray-400 text-[10px] uppercase font-bold ml-1">HR-locked</span></label>
                    <input type="text" value="{{ $emp->middle_name }}" disabled class="lmt-input bg-gray-50 dark:bg-slate-800 cursor-not-allowed"/>
                </div>
                <div>
                    <label class="lmt-label">Last name <span class="text-gray-400 text-[10px] uppercase font-bold ml-1">HR-locked</span></label>
                    <input type="text" value="{{ $emp->last_name }}" disabled class="lmt-input bg-gray-50 dark:bg-slate-800 cursor-not-allowed"/>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="lmt-label">Preferred name <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="preferred_name" maxlength="100"
                           value="{{ old('preferred_name', $emp->preferred_name) }}"
                           class="lmt-input"
                           placeholder="What you like to be called"/>
                    <p class="lmt-help">Shown on chats, mentions, and team displays.</p>
                    @error('preferred_name') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Personal email <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="email" name="personal_email" maxlength="200"
                           value="{{ old('personal_email', $emp->personal_email) }}"
                           class="lmt-input"
                           placeholder="you@example.com"/>
                    <p class="lmt-help">Used for offboarding only. Never shared with the team.</p>
                    @error('personal_email') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="lmt-label">Phone <span class="text-gray-400 font-normal">(work)</span></label>
                    <input type="tel" name="phone" maxlength="50"
                           value="{{ old('phone', $emp->phone) }}"
                           class="lmt-input"
                           placeholder="+1 555 0123"/>
                    @error('phone') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Mobile <span class="text-gray-400 font-normal">(personal)</span></label>
                    <input type="tel" name="mobile" maxlength="50"
                           value="{{ old('mobile', $emp->mobile) }}"
                           class="lmt-input"
                           placeholder="+1 555 0123"/>
                    @error('mobile') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="lmt-label">Date of birth
                        @if($emp->date_of_birth)
                            <span class="text-gray-400 text-[10px] uppercase font-bold ml-1">Locked once set</span>
                        @endif
                    </label>
                    <input type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', optional($emp->date_of_birth)->toDateString()) }}"
                           max="{{ now()->subYears(15)->toDateString() }}"
                           @if($emp->date_of_birth) disabled @endif
                           class="lmt-input @if($emp->date_of_birth) bg-gray-50 dark:bg-slate-800 cursor-not-allowed @endif"/>
                    @if(! $emp->date_of_birth)
                        <p class="lmt-help">Contact HR to change once set.</p>
                    @endif
                    @error('date_of_birth') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Marital status <span class="text-gray-400 font-normal">(optional)</span></label>
                    <select name="marital_status" class="lmt-input">
                        <option value="">— Select —</option>
                        @foreach(['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'separated' => 'Separated', 'domestic_partnership' => 'Domestic partnership'] as $key => $label)
                            <option value="{{ $key }}" {{ old('marital_status', $emp->marital_status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('marital_status') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Nationality <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="nationality" maxlength="100"
                           value="{{ old('nationality', $emp->nationality) }}"
                           class="lmt-input"
                           placeholder="e.g. Canadian"/>
                    @error('nationality') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-slate-700">
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save changes
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB 2 — ADDRESS
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='address'" x-cloak>
        <form action="{{ route('employee.profile.update', $tenantSlug) }}" method="POST" class="lmt-card">
            @csrf
            @method('PUT')

            <div class="mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Home Address</h2>
                <p class="text-xs text-gray-500 mt-0.5">Where you live. Used for tax purposes and emergency notifications.</p>
            </div>

            <div class="grid gap-4 mb-5">
                <div>
                    <label class="lmt-label">Address line 1</label>
                    <input type="text" name="address_line1" maxlength="200"
                           value="{{ old('address_line1', $emp->address_line1) }}"
                           class="lmt-input"
                           placeholder="Street address"/>
                    @error('address_line1') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Address line 2 <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="address_line2" maxlength="200"
                           value="{{ old('address_line2', $emp->address_line2) }}"
                           class="lmt-input"
                           placeholder="Apartment, suite, unit, building, floor, etc."/>
                    @error('address_line2') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                <div>
                    <label class="lmt-label">City</label>
                    <input type="text" name="city" maxlength="100"
                           value="{{ old('city', $emp->city) }}"
                           class="lmt-input"/>
                    @error('city') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">State / Province</label>
                    <input type="text" name="state" maxlength="100"
                           value="{{ old('state', $emp->state) }}"
                           class="lmt-input"/>
                    @error('state') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Postal code</label>
                    <input type="text" name="postal_code" maxlength="20"
                           value="{{ old('postal_code', $emp->postal_code) }}"
                           class="lmt-input"/>
                    @error('postal_code') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="lmt-label">Country</label>
                    <input type="text" name="country" maxlength="100"
                           value="{{ old('country', $emp->country) }}"
                           class="lmt-input"
                           placeholder="e.g. Canada"/>
                    @error('country') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- preserve personal fields in this form submission --}}
            <input type="hidden" name="preferred_name" value="{{ $emp->preferred_name }}"/>
            <input type="hidden" name="personal_email" value="{{ $emp->personal_email }}"/>
            <input type="hidden" name="phone" value="{{ $emp->phone }}"/>
            <input type="hidden" name="mobile" value="{{ $emp->mobile }}"/>
            <input type="hidden" name="marital_status" value="{{ $emp->marital_status }}"/>
            <input type="hidden" name="nationality" value="{{ $emp->nationality }}"/>
            @if(! $emp->date_of_birth)
                <input type="hidden" name="date_of_birth" value=""/>
            @endif

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-slate-700">
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save address
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB 3 — EMERGENCY CONTACTS
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='emergency'" x-cloak x-data="emergencyContacts()">
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Emergency Contacts</h2>
                    <p class="text-xs text-gray-500 mt-0.5">People to call in case of an emergency. Up to 5.</p>
                </div>
                @if($emergencyContacts->count() < 5)
                    <button @click="openAdd()" type="button" class="lmt-btn-primary lmt-btn-sm">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Add contact
                    </button>
                @endif
            </div>

            @if($emergencyContacts->isEmpty())
                <div class="text-center py-12">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <i data-lucide="phone-call" class="w-6 h-6 text-gray-300"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No emergency contacts yet</p>
                    <p class="text-xs text-gray-500 mt-1">Add at least one person we can reach in an emergency.</p>
                    <button @click="openAdd()" type="button" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Add first contact
                    </button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($emergencyContacts as $c)
                        <div class="border border-gray-100 dark:border-slate-700 rounded-2xl p-4 hover:border-gray-200 dark:hover:border-slate-600 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-black text-white flex-shrink-0"
                                         style="background:var(--brand-500);">
                                        {{ strtoupper(substr($c->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $c->name }}</p>
                                            @if($c->is_primary)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase"
                                                      style="background:var(--brand-50);color:var(--brand-600);">Primary</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ ucfirst($c->relationship) }}</p>
                                        <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-600 dark:text-slate-300">
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="phone" class="w-3 h-3 text-gray-400"></i>
                                                {{ $c->phone }}
                                            </span>
                                            @if($c->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i>
                                                    {{ $c->email }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($c->address)
                                            <p class="text-[11px] text-gray-500 mt-1 flex items-start gap-1">
                                                <i data-lucide="map-pin" class="w-3 h-3 text-gray-400 mt-0.5 flex-shrink-0"></i>
                                                <span>{{ $c->address }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-1 flex-shrink-0">
                                    <button @click='openEdit(@json($c))' type="button"
                                            class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button @click="openDelete({{ $c->id }}, '{{ addslashes($c->name) }}')" type="button"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ADD/EDIT MODAL --}}
        <div x-show="formOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="formOpen=false">
            <div class="lmt-modal" @click.outside="formOpen=false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100 mb-1" x-text="editing ? 'Edit emergency contact' : 'Add emergency contact'"></h3>
                <p class="text-xs text-gray-500 mb-5">All fields marked with * are required.</p>

                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PUT"/>
                    </template>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="lmt-label">Full name *</label>
                            <input type="text" name="name" required maxlength="200"
                                   x-model="form.name"
                                   class="lmt-input"/>
                        </div>
                        <div>
                            <label class="lmt-label">Relationship *</label>
                            <select name="relationship" required class="lmt-input" x-model="form.relationship">
                                <option value="">— Select —</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Partner">Partner</option>
                                <option value="Parent">Parent</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Child">Child</option>
                                <option value="Friend">Friend</option>
                                <option value="Relative">Relative</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="lmt-label">Phone *</label>
                            <input type="tel" name="phone" required maxlength="50"
                                   x-model="form.phone"
                                   placeholder="+1 555 0123"
                                   class="lmt-input"/>
                        </div>
                        <div>
                            <label class="lmt-label">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="email" name="email" maxlength="200"
                                   x-model="form.email"
                                   class="lmt-input"/>
                        </div>
                    </div>

                    <div>
                        <label class="lmt-label">Address <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea name="address" rows="2" maxlength="500"
                                  x-model="form.address"
                                  placeholder="Street, city, postal code"
                                  class="lmt-textarea"></textarea>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <input type="checkbox" name="is_primary" value="1"
                               x-model="form.is_primary"
                               class="w-4 h-4 rounded cursor-pointer"
                               style="accent-color:var(--brand-500);"/>
                        <span class="text-sm font-bold text-gray-700 dark:text-slate-300">Make this my primary contact</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-slate-700">
                        <button type="button" @click="formOpen=false" class="lmt-btn-secondary">Cancel</button>
                        <button type="submit" class="lmt-btn-primary" :disabled="submitting" @click="submitting=true">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span x-show="!submitting" x-text="editing ? 'Update' : 'Add'"></span>
                            <span x-show="submitting">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- DELETE MODAL --}}
        <div x-show="deleteOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="deleteOpen=false">
            <div class="lmt-modal" @click.outside="deleteOpen=false">
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center mb-4">
                        <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Remove this contact?</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        <span class="font-bold" x-text="deleteName"></span> will be removed from your emergency contacts.
                    </p>
                </div>
                <div class="flex justify-center gap-2 mt-6">
                    <button @click="deleteOpen=false" class="lmt-btn-secondary">Keep</button>
                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="lmt-btn-danger">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Remove
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB 4 — EMPLOYMENT (read-only)
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='employment'" x-cloak>
        <div class="lmt-card">
            <div class="mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Employment Information</h2>
                <p class="text-xs text-gray-500 mt-0.5">Read-only details about your role. Contact HR for changes.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                @php
                    $employmentStatus = $emp->employment_status ? ucfirst(str_replace('_', ' ', $emp->employment_status)) : '—';
                    $employmentType   = $emp->employment_type ? ucfirst(str_replace('_', ' ', $emp->employment_type)) : '—';

                    $rows = [
                        ['Employee code', $emp->employee_code ?? '—', 'hash', false],
                        ['Position', $emp->position?->title ?? '—', 'briefcase', false],
                        ['Department', $emp->department?->name ?? '—', 'git-branch', false],
                        ['Location', $emp->location?->name ?? '—', 'map-pin', false],
                        ['Direct manager', $emp->manager?->full_name ?? '—', 'user-check', false],
                        ['Hire date', $emp->hire_date?->format('F j, Y') ?? '—', 'calendar', false],
                        ['Employment type', $employmentType, 'clock', false],
                        ['Employment status', $employmentStatus, 'activity', false],
                    ];

                    if ($emp->probation_end_date) {
                        $rows[] = ['Probation ends', $emp->probation_end_date->format('M j, Y'), 'calendar-clock', false];
                    }
                    if ($emp->confirmation_date) {
                        $rows[] = ['Confirmed on', $emp->confirmation_date->format('M j, Y'), 'badge-check', false];
                    }
                @endphp

                @foreach($rows as [$label, $value, $icon, $sensitive])
                    <div class="flex items-start justify-between gap-3 py-2 border-b border-gray-50 dark:border-slate-800">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                            <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                            <span>{{ $label }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 text-right">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="lmt-alert lmt-alert-info mt-5">
                <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                <p class="text-xs">Salary, tax, and bank details are managed privately by HR. Contact them directly for any changes.</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB 5 — SECURITY (password)
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='security'" x-cloak>
        <div class="lmt-card">
            <div class="mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Security</h2>
                <p class="text-xs text-gray-500 mt-0.5">Change your password. Use something strong and unique.</p>
            </div>

            <form action="{{ route('employee.profile.password', $tenantSlug) }}" method="POST"
                  x-data="{ showCurrent: false, showNew: false, showConfirm: false, submitting: false }"
                  @submit="submitting=true"
                  class="max-w-md space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="lmt-label">Current password *</label>
                    <div class="relative">
                        <input :type="showCurrent ? 'text' : 'password'"
                               name="current_password" required
                               autocomplete="current-password"
                               class="lmt-input pr-10"/>
                        <button type="button" @click="showCurrent = !showCurrent"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                            <i :data-lucide="showCurrent ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                        </button>
                    </div>
                    @error('current_password') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="lmt-label">New password *</label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'"
                               name="password" required minlength="8"
                               autocomplete="new-password"
                               class="lmt-input pr-10"/>
                        <button type="button" @click="showNew = !showNew"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                            <i :data-lucide="showNew ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <p class="lmt-help">At least 8 characters.</p>
                    @error('password') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="lmt-label">Confirm new password *</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'"
                               name="password_confirmation" required minlength="8"
                               autocomplete="new-password"
                               class="lmt-input pr-10"/>
                        <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                            <i :data-lucide="showConfirm ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="lmt-alert lmt-alert-info">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0"></i>
                    <p class="text-xs">Use a mix of letters, numbers, and symbols. Don't reuse passwords from other sites.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary" :disabled="submitting">
                        <i data-lucide="key" class="w-4 h-4"></i>
                        <span x-show="!submitting">Change password</span>
                        <span x-show="submitting">Updating…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function emergencyContacts() {
    return {
        formOpen: false,
        deleteOpen: false,
        editing: false,
        submitting: false,
        form: { id: null, name: '', relationship: '', phone: '', email: '', address: '', is_primary: false },
        deleteId: null,
        deleteName: '',
        storeUrl:  "{{ route('employee.profile.emergency.store', $tenantSlug) }}",
        updateUrlTemplate: "{{ rtrim(route('employee.profile.emergency.update', [$tenantSlug, 'PLACEHOLDER']), '/') }}",
        deleteUrlTemplate: "{{ rtrim(route('employee.profile.emergency.destroy', [$tenantSlug, 'PLACEHOLDER']), '/') }}",

        get formAction() {
            if (this.editing && this.form.id) {
                return this.updateUrlTemplate.replace('PLACEHOLDER', this.form.id);
            }
            return this.storeUrl;
        },
        get deleteUrl() {
            return this.deleteId ? this.deleteUrlTemplate.replace('PLACEHOLDER', this.deleteId) : '';
        },

        openAdd() {
            this.editing = false;
            this.submitting = false;
            this.form = { id: null, name: '', relationship: '', phone: '', email: '', address: '', is_primary: false };
            this.formOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        openEdit(contact) {
            this.editing = true;
            this.submitting = false;
            this.form = {
                id: contact.id,
                name: contact.name || '',
                relationship: contact.relationship || '',
                phone: contact.phone || '',
                email: contact.email || '',
                address: contact.address || '',
                is_primary: !!contact.is_primary,
            };
            this.formOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        openDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.deleteOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection