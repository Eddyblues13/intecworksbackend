@extends('admin.layouts.app')

@section('heading', 'API Settings')
@section('subheading', 'Manage payment gateway API keys.')

@section('content')
<div class="max-w-3xl space-y-6">
    <form method="POST" action="{{ route('admin.api-settings.update') }}">
        @csrf

        {{-- Paystack --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <x-admin-icon name="credit-card" class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Paystack</h3>
                    <p class="text-xs text-gray-400">Manage your Paystack API credentials</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Public Key</label>
                    <input type="text" name="paystack_public_key"
                           value="{{ $settings['paystack_public_key'] }}"
                           placeholder="pk_live_..."
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Secret Key</label>
                    <input type="password" name="paystack_secret_key"
                           value="{{ $settings['paystack_secret_key'] }}"
                           placeholder="sk_live_..."
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
            </div>
        </div>

        {{-- KoraPay --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5 mt-6">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <x-admin-icon name="dollar-sign" class="w-5 h-5 text-emerald-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">KoraPay</h3>
                    <p class="text-xs text-gray-400">Manage your KoraPay API credentials</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Public Key</label>
                    <input type="text" name="korapay_public_key"
                           value="{{ $settings['korapay_public_key'] }}"
                           placeholder="pk_live_..."
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Secret Key</label>
                    <input type="password" name="korapay_secret_key"
                           value="{{ $settings['korapay_secret_key'] }}"
                           placeholder="sk_live_..."
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Encryption Key</label>
                    <input type="password" name="korapay_encryption_key"
                           value="{{ $settings['korapay_encryption_key'] }}"
                           placeholder="Optional"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
            </div>
        </div>

        {{-- Info banner --}}
        <div class="mt-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start gap-2">
            <x-admin-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
            <span>API keys are stored securely in the database. Never share secret keys publicly.</span>
        </div>

        <div class="mt-4">
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
                Save API Settings
            </button>
        </div>
    </form>
</div>
@endsection
