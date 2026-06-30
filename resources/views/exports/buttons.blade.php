{{--
  Reusable export dropdown.
  Usage: @include('exports.buttons', ['route' => 'admin.employees.export', 'params' => [$tenant]])
         @include('exports.buttons', ['route' => 'admin.attendance.export', 'params' => [$tenant], 'extra' => ['from' => $from, 'to' => $to]])
--}}
@php $extra = $extra ?? []; @endphp
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="lmt-btn-secondary lmt-btn-sm flex items-center gap-1.5">
        <i data-lucide="download" class="w-4 h-4"></i>
        Export
        <i data-lucide="chevron-down" class="w-3 h-3"></i>
    </button>
    <div x-show="open" @click.outside="open = false" x-cloak
         class="absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1">
        <a href="{{ route($route, array_merge($params, array_merge($extra, ['format' => 'excel']))) }}"
           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <i data-lucide="table-2" class="w-4 h-4 text-green-600"></i>
            Excel (.xlsx)
        </a>
        <a href="{{ route($route, array_merge($params, array_merge($extra, ['format' => 'pdf']))) }}"
           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <i data-lucide="file-text" class="w-4 h-4 text-red-500"></i>
            PDF
        </a>
    </div>
</div>
