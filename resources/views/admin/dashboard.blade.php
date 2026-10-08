<!DOCTYPE html>
<html lang="zh-TW" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台 | Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col font-sans">
    <nav class="bg-slate-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-sm">
                        QJ
                    </div>
                    <span class="font-semibold text-lg">後台管理系統</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-slate-300">歡迎，<strong class="text-white">{{ Auth::user()->name ?? Auth::user()->username }}</strong></span>
                    <form action="/admin/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white font-medium transition-colors">
                            登出
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
            <h1 class="text-2xl font-bold text-slate-800 mb-2">歡迎回到控制台</h1>
            <p class="text-slate-600">此頁面受到 Authentication Middleware 保護。您已成功登入系統。</p>
        </div>
    </main>
</body>
</html>
