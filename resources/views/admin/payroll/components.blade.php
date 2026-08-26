@extends('layouts.admin')
@section('title','Salary Components')
@section('page-title','Salary Components')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Salary Components</h2>
        <p class="text-sm text-gray-800">Define earnings, deductions, and benefits</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.payroll.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
        <button onclick="document.getElementById('add-comp-modal').classList.remove('hidden');document.getElementById('add-comp-modal').classList.add('flex');"
                class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Component
        </button>
    </div>
</div>

@php
$grouped = $components->groupBy('type');
$typeColors = ['earning'=>'lmt-badge-green','deduction'=>'lmt-badge-red','tax'=>'lmt-badge-amber','benefit'=>'lmt-badge-brand'];
@endphp

<div class="grid md:grid-cols-2 gap-6">
    @foreach(['earning'=>'Earnings','deduction'=>'Deductions','tax'=>'Taxes','benefit'=>'Benefits'] as $type => $label)
    <div class="lmt-card p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <span class="{{ $typeColors[$type] ?? 'lmt-badge-gray' }} text-xs">{{ $label }}</span>
            </div>
            <span class="text-xs text-gray-800">{{ $grouped->get($type, collect())->count() }} components</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($grouped->get($type, collect()) as $comp)
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ $comp->name }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $comp->code }}</code>
                        <span class="text-xs text-gray-800 capitalize">{{ $comp->calculation }}</span>
                        @if($comp->default_amount)
                        <span class="text-xs text-gray-800">
                            · {{ $comp->calculation === 'percentage' ? $comp->default_amount.'%' : '$'.$comp->default_amount }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($comp->is_taxable)
                    <span class="lmt-badge-amber text-xs">Taxable</span>
                    @endif
                    @if($comp->is_mandatory)
                    <span class="lmt-badge-brand text-xs">Mandatory</span>
                    @endif
                    <span class="{{ $comp->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                        {{ $comp->is_active ? 'Active' : 'Off' }}
                    </span>
                    <span class="text-xs text-gray-800">{{ $comp->employee_salary_components_count }} staff</span>
                </div>
            </div>
            @empty
            <div class="px-4 py-6 text-center text-sm text-gray-800">
                No {{ strtolower($label) }} defined
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- Add Component Modal --}}
<div id="add-comp-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Salary Component</h3>
        <form action="{{ route('admin.payroll.components.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Housing Allowance"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="HRA"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                        <option value="tax">Tax</option>
                        <option value="benefit">Benefit</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Calculation <span class="text-red-500">*</span></label>
                    <select name="calculation" required class="lmt-select">
                        <option value="fixed">Fixed Amount ($)</option>
                        <option value="percentage">% of Basic</option>
                        <option value="formula">Formula</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Default Amount</label>
                    <input type="number" name="default_amount" step="0.01" class="lmt-input" placeholder="0.00"/>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach([['is_taxable','Taxable'],['is_mandatory','Mandatory']] as [$n,$l])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="{{ $n }}" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-800">{{ $l }}</span>
                </label>
                @endforeach
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button"
                        onclick="document.getElementById('add-comp-modal').classList.add('hidden');document.getElementById('add-comp-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush