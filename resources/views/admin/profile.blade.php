@extends('admin.layouts.app')

@section('heading', 'Profile')
@section('subheading', 'Manage your admin account.')

@section('content')
<div class="max-w-2xl space-y-6">
    {{-- Profile Info --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Account Information</h3>
        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="{{ $admin->full_name }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ $admin->email }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ $admin->phone }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
            </div>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
                Update Profile
            </button>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Change Password</h3>
        <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
                </div>
            </div>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition">
                Change Password
            </button>
        </form>
    </div>
</div>
@endsection
