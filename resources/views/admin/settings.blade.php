@extends('admin.layouts.app')

@section('heading', 'Settings')
@section('subheading', 'Platform configuration.')

@section('content')
<div class="max-w-2xl space-y-6">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5">
            <h3 class="font-semibold text-gray-900">Platform Settings</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Commission Percentage</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="commissionPercent" value="{{ $commission }}" min="0" max="100" step="0.1"
                           class="w-32 px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                    <span class="text-sm text-gray-500">%</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Commission charged on each completed transaction.</p>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
