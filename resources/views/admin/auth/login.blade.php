<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — IntecWorks</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-[#0F2556] flex items-center justify-center p-4">

<div class="w-full max-w-md">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#EE7963] text-white font-bold text-xl mb-4">
            IW
        </div>
        <h1 class="text-2xl font-bold text-white">IntecWorks Admin</h1>
        <p class="text-white/60 mt-1 text-sm">Sign in to access the admin dashboard</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        @if ($errors->any())
            <div class="mb-6 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900
                              focus:outline-none focus:ring-2 focus:ring-[#1E3D84] focus:border-transparent
                              placeholder:text-gray-400 transition"
                       placeholder="admin@intecworks.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900
                              focus:outline-none focus:ring-2 focus:ring-[#1E3D84] focus:border-transparent
                              placeholder:text-gray-400 transition"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#1E3D84] focus:ring-[#1E3D84]">
                    Remember me
                </label>
            </div>

            <button type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-[#1E3D84] text-white font-semibold
                           hover:bg-[#0F2556] focus:outline-none focus:ring-2 focus:ring-offset-2
                           focus:ring-[#1E3D84] transition shadow-lg shadow-[#1E3D84]/25">
                Sign In
            </button>
        </form>
    </div>

    <p class="text-center text-white/40 text-xs mt-6">&copy; {{ date('Y') }} IntecWorks. All rights reserved.</p>
</div>

</body>
</html>
