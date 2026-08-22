@extends('layouts.admin')
@section('title','Org Chart')
@section('page-title','Org Chart')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Organizational Chart</h2>
        <p class="text-sm text-gray-800 mt-0.5">{{ $employees->count() }} active employees</p>
    </div>
    <a href="{{ route('admin.employees.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
        <i data-lucide="list" class="w-4 h-4"></i>
        List View
    </a>
</div>

<div class="lmt-card overflow-auto">
    <div id="org-tree" class="min-w-max p-8"></div>
</div>

@endsection

@push('scripts')
<script>
const employees = @json($employees);

// Build tree structure
function buildTree(employees, managerId = null) {
    return employees
        .filter(e => e.manager_id == managerId)
        .map(e => ({ ...e, children: buildTree(employees, e.id) }));
}

function renderNode(emp) {
    const initials = (emp.first_name[0] || '') + (emp.last_name[0] || '');
    return `
        <div class="inline-flex flex-col items-center">
            <a href="/t/${window.tenantSlug}/admin/employees/${emp.id}"
               class="flex flex-col items-center p-3 rounded-2xl border-2 border-gray-100
                      hover:border-brand-300 hover:shadow-pop transition-all bg-white group w-36">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-sm mb-2 group-hover:scale-110 transition-transform"
                     style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                    ${initials}
                </div>
                <p class="font-bold text-gray-900 text-xs text-center leading-tight truncate w-full text-center">
                    ${emp.first_name} ${emp.last_name}
                </p>
                <p class="text-gray-800 text-[10px] text-center truncate w-full mt-0.5">
                    ${emp.position?.title || 'No Position'}
                </p>
            </a>
        </div>
    `;
}

function renderTree(nodes, level = 0) {
    if (!nodes.length) return '';

    if (level === 0 && nodes.length === 1) {
        const node = nodes[0];
        return `
            <div class="flex flex-col items-center">
                ${renderNode(node)}
                ${node.children.length ? `
                    <div class="w-0.5 h-6 bg-gray-200 mt-2"></div>
                    <div class="flex gap-8 relative">
                        <div class="absolute top-0 left-0 right-0 h-0.5 bg-gray-200"
                             style="left:calc(50% - ${(node.children.length - 1) * 88}px);
                                    right:calc(50% - ${(node.children.length - 1) * 88}px);"></div>
                        ${node.children.map(child => `
                            <div class="flex flex-col items-center">
                                <div class="w-0.5 h-6 bg-gray-200"></div>
                                ${renderTree([child], level + 1)}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    return nodes.map(node => `
        <div class="flex flex-col items-center">
            ${renderNode(node)}
            ${node.children.length ? `
                <div class="w-0.5 h-5 bg-gray-200 mt-1.5"></div>
                <div class="flex gap-6">
                    ${renderTree(node.children, level + 1)}
                </div>
            ` : ''}
        </div>
    `).join('<div class="w-8"></div>');
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    window.tenantSlug = '{{ $tenant }}';
    const tree = buildTree(employees);
    document.getElementById('org-tree').innerHTML = renderTree(tree);
});
</script>
@endpush