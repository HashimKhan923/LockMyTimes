{{-- Vote form partial — used both inline on poll detail and in the "change vote" toggle --}}
<form action="{{ route('employee.announcements.polls.vote', [$tenantSlug, $poll->id]) }}"
      method="POST"
      @submit="submitting=true">
    @csrf

    @if($poll->type === 'multiple_choice')
        <p class="text-xs text-gray-800 mb-3">Pick one or more options that apply.</p>
    @elseif($poll->type === 'rating')
        <p class="text-xs text-gray-800 mb-3">Tap a rating to vote.</p>
    @else
        <p class="text-xs text-gray-800 mb-3">Pick one option.</p>
    @endif

    <div class="space-y-2">
        @foreach($poll->options ?? [] as $idx => $option)
            @php
                $label = is_array($option) ? ($option['text'] ?? $option['label'] ?? (string) $idx) : (string) $option;
            @endphp
            <label class="block cursor-pointer">
                <input type="checkbox" name="selected_options[]" value="{{ $idx }}"
                       :checked="isChecked({{ $idx }})"
                       @change="toggle({{ $idx }})"
                       class="sr-only"/>
                <div class="p-3 rounded-2xl border-2 transition-all"
                     :class="isChecked({{ $idx }}) ? '' : 'border-gray-200 hover:border-gray-300 dark:border-slate-700 dark:hover:border-slate-600'"
                     :style="isChecked({{ $idx }}) ? 'background:var(--brand-50);border-color:var(--brand-500);' : ''">
                    <div class="flex items-center gap-3">
                        @if($poll->type === 'rating')
                            <div class="flex items-center gap-0.5">
                                @for($s = 1; $s <= ($idx + 1); $s++)
                                    <i data-lucide="star" class="w-4 h-4 fill-current" style="color:#F59E0B;"></i>
                                @endfor
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $label }}</span>
                        @else
                            <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                  :class="isChecked({{ $idx }}) ? 'border-transparent' : 'border-gray-300 dark:border-slate-600'"
                                  :style="isChecked({{ $idx }}) ? 'background:var(--brand-500);' : ''">
                                <span x-show="isChecked({{ $idx }})" class="w-1.5 h-1.5 bg-white rounded-full"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $label }}</span>
                        @endif
                    </div>
                </div>
            </label>
        @endforeach
    </div>

    <div class="flex justify-end mt-5">
        <button type="submit" class="lmt-btn-primary" :disabled="submitting || choices.length === 0">
            <i data-lucide="send" class="w-4 h-4"></i>
            <span x-show="!submitting">Submit vote</span>
            <span x-show="submitting">Submitting…</span>
        </button>
    </div>
</form>