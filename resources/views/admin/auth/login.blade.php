<!DOCTYPE html>
<html lang="zh-TW" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台登入 | Admin Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 sm:p-6 lg:p-8 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen">
    <div class="w-full max-w-md space-y-8 bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-2xl border border-white/20 transition-all">
        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 text-white font-bold text-2xl shadow-lg shadow-indigo-500/30 mb-2">
                QJ
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-800">後台管理系統</h2>
            <p class="text-sm text-slate-500">請輸入您的帳號與密碼以繼續</p>
        </div>

        <!-- Alert messages -->
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm space-y-1">
                <div class="flex items-center font-semibold text-rose-800 mb-1">
                    <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    登入失敗
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-xs sm:text-sm pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form action="/admin/login" method="POST" class="mt-6 space-y-6" x-data="{ showPassword: false, loading: false }" @submit="loading = true">
            @csrf

            <div class="space-y-4">
                <!-- Username or Email -->
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1.5">帳號或 Email</label>
                    <div class="relative">
                        <input id="username" name="username" type="text" autocomplete="username" required
                            value="{{ old('username') }}"
                            placeholder="admin / admin@example.com"
                            class="w-full px-4 py-3 text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-slate-400 text-sm">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">密碼</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                            placeholder="••••••••"
                            class="w-full px-4 py-3 text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-slate-400 text-sm pr-12">

                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none"
                            title="顯示/隱藏密碼">
                            <!-- Eye icon -->
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye Slash icon -->
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 0110.122 5.937M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center space-x-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                        <span>保持登入</span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" :disabled="loading"
                    class="w-full flex justify-center items-center py-3.5 px-4 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/25 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all disabled:opacity-75 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? '登入中...' : '登入'">登入</span>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
