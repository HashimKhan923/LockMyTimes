@extends('layouts.employee')

@section('title', 'Request Attendance Correction')
@section('page-title', 'Request Attendance Correction')

@section('content')
<div class="max-w-2xl mx-auto">

    <a href="{{ route('employee.attendance-corrections.index', $tenant) }}"
       class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-800 hover:text-gray-800 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to corrections
    </a>

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif">
            Request Attendance Correction
        </h1>
        <p class="text-sm text-gray-800 mt-1">Forgot to clock in or out? Submit the correct time and your manager or admin will review it.</p>
    </div>

    @if($errors->any())
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('employee.attendance-corrections.store', $tenant) }}" method="POST" class="lmt-card space-y-6">
        @csrf

        <div>
            <label class="lmt-label">Date <span class="text-red-500">*</span></label>
            <input type="date" name="work_date" required max="{{ today()->format('Y-m-d') }}"
                   class="lmt-input" value="{{ old('work_date') }}"/>
            @error('work_date') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="lmt-label">Correct Clock In</label>
                <input type="time" name="clock_in" class="lmt-input" value="{{ old('clock_in') }}"/>
                @error('clock_in') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="lmt-label">Correct Clock Out</label>
                <input type="time" name="clock_out" class="lmt-input" value="{{ old('clock_out') }}"/>
                @error('clock_out') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="lmt-help -mt-4">Fill in whichever one you missed — you don't need both. Leave the other blank to keep it as-is.</p>

        <div>
            <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
            <textarea name="reason" required minlength="5" maxlength="1000"
                      class="lmt-textarea" rows="3"
                      placeholder="Explain briefly why you're requesting this correction…">{{ old('reason') }}</textarea>
            <p class="lmt-help">5–1000 characters.</p>
            @error('reason') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2 border-t border-gray-100">
            <a href="{{ route('employee.attendance-corrections.index', $tenant) }}" class="lmt-btn-secondary">Cancel</a>
            <button type="submit" class="lmt-btn-primary">
                <i data-lucide="send" class="w-4 h-4"></i>
                Submit Request
            </button>
        </div>
    </form>
</div>
@endsection
