<div x-show="breakOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="breakOpen=false">
    <div class="lmt-modal" @click.outside="breakOpen=false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Start a Break</h3>
            <button @click="breakOpen=false" class="text-gray-800 hover:text-gray-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <p class="text-sm text-gray-800 mb-4">Pick a break type. We'll start the timer right away.</p>

        <div class="space-y-2 mb-5">
            @php
                $bt = [
                    ['key'=>'lunch',    'icon'=>'utensils', 'label'=>'Lunch'],
                    ['key'=>'tea',      'icon'=>'coffee',   'label'=>'Tea / Coffee'],
                    ['key'=>'personal', 'icon'=>'user',     'label'=>'Personal'],
                    ['key'=>'other',    'icon'=>'more-horizontal','label'=>'Other'],
                ];
            @endphp
            @foreach($bt as $b)
                <button @click="breakType='{{ $b['key'] }}'"
                        :class="breakType==='{{ $b['key'] }}' ? 'border-brand-500 bg-brand-50' : 'border-gray-200 hover:border-gray-300'"
                        class="w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                         :class="breakType==='{{ $b['key'] }}' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-800'">
                        <i data-lucide="{{ $b['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <span class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $b['label'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="flex justify-end gap-2">
            <button @click="breakOpen=false" class="lmt-btn-secondary">Cancel</button>
            <button @click="startBreak()" class="lmt-btn-primary" :disabled="breakSubmitting">
                <span x-show="!breakSubmitting"><i data-lucide="play" class="w-4 h-4"></i> Start Break</span>
                <span x-show="breakSubmitting">Starting…</span>
            </button>
        </div>
    </div>
</div>
