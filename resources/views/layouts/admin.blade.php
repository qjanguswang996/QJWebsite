<!DOCTYPE html>
<html lang="zh-TW" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - 3D 解析後台管理系統</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-50/50 selection:bg-indigo-500 selection:text-white"
      x-data="{
          mobileMenuOpen: false,
          sidebarCollapsed: false,
          userMenuOpen: false
      }">

    <!-- Mobile Drawer Backdrop -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs lg:hidden"
         @click="mobileMenuOpen = false"
         x-cloak></div>

    <!-- Mobile Drawer Navigation -->
    <aside x-show="mobileMenuOpen"
           x-transition:enter="transition ease-in-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in-out duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200/80 flex flex-col lg:hidden shadow-2xl"
           x-cloak>
        <!-- Mobile Sidebar Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100">
            <a href="/admin" class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-xs">
                    <x-icon name="box" class="w-5 h-5" />
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-base text-slate-900 tracking-tight">3D Analytics</span>
                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">SaaS Admin</span>
                </div>
            </a>
            <button @click="mobileMenuOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <x-icon name="x" class="w-5 h-5" />
            </button>
        </div>

        <!-- Mobile Navigation Menu -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <a href="/admin" class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->is('admin') ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <x-icon name="layout-dashboard" class="w-5 h-5 mr-3 {{ request()->is('admin') ? 'text-indigo-600' : 'text-slate-400' }}" />
                <span>控制台儀表板</span>
            </a>
            <a href="#" class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="layers" class="w-5 h-5 mr-3 text-slate-400" />
                <span>3D 檔案解析</span>
            </a>
            <a href="#" class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="database" class="w-5 h-5 mr-3 text-slate-400" />
                <span>解析紀錄資料</span>
            </a>
            <a href="#" class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="settings" class="w-5 h-5 mr-3 text-slate-400" />
                <span>系統參數設定</span>
            </a>
        </nav>

        <!-- Mobile Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <div class="flex items-center space-x-3 p-2 rounded-xl bg-white border border-slate-200/60 shadow-2xs">
                <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                    A
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">System Admin</p>
                    <p class="text-xs text-slate-400 truncate">admin@example.com</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Desktop & Tablet Sidebar -->
    <aside class="hidden md:fixed md:inset-y-0 md:left-0 md:z-30 md:flex md:flex-col bg-white border-r border-slate-200/80 transition-all duration-300"
           :class="sidebarCollapsed ? 'md:w-20' : 'md:w-64'">

        <!-- Sidebar Header -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100">
            <a href="/admin" class="flex items-center space-x-3 overflow-hidden">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-xs flex-shrink-0">
                    <x-icon name="box" class="w-5 h-5" />
                </div>
                <div class="flex flex-col whitespace-nowrap transition-opacity duration-200" x-show="!sidebarCollapsed">
                    <span class="font-bold text-base text-slate-900 tracking-tight">3D Analytics</span>
                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">SaaS Admin</span>
                </div>
            </a>

            <button @click="sidebarCollapsed = !sidebarCollapsed"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors hidden md:block"
                    :title="sidebarCollapsed ? '展開選單' : '縮小選單'">
                <x-icon name="chevron-left" class="w-4 h-4 transition-transform duration-200" ::class="sidebarCollapsed ? 'rotate-180' : ''" />
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto">
            <a href="/admin"
               class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group relative {{ request()->is('admin') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
               :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <x-icon name="layout-dashboard" class="w-5 h-5 flex-shrink-0 {{ request()->is('admin') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" />
                <span class="ml-3 whitespace-nowrap" x-show="!sidebarCollapsed">控制台儀表板</span>
            </a>

            <a href="#"
               class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900 group"
               :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <x-icon name="layers" class="w-5 h-5 flex-shrink-0 text-slate-400 group-hover:text-slate-600" />
                <span class="ml-3 whitespace-nowrap" x-show="!sidebarCollapsed">3D 檔案解析</span>
            </a>

            <a href="#"
               class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900 group"
               :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <x-icon name="database" class="w-5 h-5 flex-shrink-0 text-slate-400 group-hover:text-slate-600" />
                <span class="ml-3 whitespace-nowrap" x-show="!sidebarCollapsed">解析紀錄資料</span>
            </a>

            <a href="#"
               class="flex items-center px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all text-slate-600 hover:bg-slate-50 hover:text-slate-900 group"
               :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <x-icon name="settings" class="w-5 h-5 flex-shrink-0 text-slate-400 group-hover:text-slate-600" />
                <span class="ml-3 whitespace-nowrap" x-show="!sidebarCollapsed">系統參數設定</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-3 border-t border-slate-100 bg-slate-50/50">
            <div class="flex items-center space-x-3 p-2 rounded-xl bg-white border border-slate-200/60 shadow-2xs"
                 :class="sidebarCollapsed ? 'justify-center p-1.5' : ''">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                    A
                </div>
                <div class="flex-1 min-w-0" x-show="!sidebarCollapsed">
                    <p class="text-xs font-semibold text-slate-800 truncate">System Admin</p>
                    <p class="text-[10px] text-slate-400 truncate">admin@example.com</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper Area -->
    <div class="flex flex-col min-h-screen transition-all duration-300"
         :class="sidebarCollapsed ? 'md:pl-20' : 'md:pl-64'">

        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-20 h-16 bg-white/80 backdrop-blur-md border-b border-slate-200/70 px-4 md:px-8 flex items-center justify-between">

            <div class="flex items-center space-x-4">
                <!-- Mobile Hamburger Button -->
                <button @click="mobileMenuOpen = true"
                        type="button"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 lg:hidden focus:outline-none">
                    <x-icon name="menu" class="w-6 h-6" />
                </button>

                <!-- Breadcrumb Component -->
                <div class="hidden sm:block">
                    @yield('breadcrumb')
                </div>
            </div>

            <!-- Topbar Right Actions -->
            <div class="flex items-center space-x-3">
                <!-- Search Bar Placeholder -->
                <div class="relative hidden sm:block w-48 md:w-64">
                    <x-icon name="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input type="text"
                           placeholder="搜尋檔案或資料..."
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-100/70 focus:bg-white border border-transparent focus:border-indigo-300 rounded-xl transition-all outline-none text-slate-700 placeholder:text-slate-400">
                </div>

                <!-- Notifications Button -->
                <button type="button" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <x-icon name="bell" class="w-5 h-5" />
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-indigo-600 ring-2 ring-white"></span>
                </button>

                <!-- Divider -->
                <div class="h-5 w-px bg-slate-200"></div>

                <!-- User Dropdown Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            @click.outside="open = false"
                            class="flex items-center space-x-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                            A
                        </div>
                        <span class="hidden md:inline-block text-xs font-semibold text-slate-700">管理員</span>
                        <x-icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400" />
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white border border-slate-200/80 rounded-xl shadow-lg py-1.5 z-50"
                         x-cloak>
                        <div class="px-3.5 py-2 border-b border-slate-100">
                            <p class="text-xs font-semibold text-slate-800">System Admin</p>
                            <p class="text-[10px] text-slate-400">admin@example.com</p>
                        </div>
                        <a href="#" class="flex items-center px-3.5 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            <x-icon name="user" class="w-4 h-4 mr-2 text-slate-400" />
                            個人設定
                        </a>
                        <a href="#" class="flex items-center px-3.5 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            <x-icon name="settings" class="w-4 h-4 mr-2 text-slate-400" />
                            系統參數
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="#" class="flex items-center px-3.5 py-2 text-xs text-rose-600 hover:bg-rose-50">
                            <x-icon name="log-out" class="w-4 h-4 mr-2 text-rose-500" />
                            登出系統
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
            <!-- Flash Message Banner Component -->
            <x-admin.flash-message />

            <!-- Page Heading -->
            @hasSection('header')
                <div class="mb-6">
                    @yield('header')
                </div>
            @endif

            <!-- Main Page Slot -->
            @yield('content')
        </main>

        <!-- Page Footer -->
        <footer class="mt-auto py-4 px-8 border-t border-slate-200/60 bg-white/50 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400">
            <div>
                &copy; {{ date('Y') }} 3D File Analysis & Rendering System. All rights reserved.
            </div>
            <div class="mt-2 sm:mt-0 flex space-x-4">
                <span>Tailwind CSS v4</span>
                <span>•</span>
                <span>Lucide Icons</span>
                <span>•</span>
                <span>Three.js Engine</span>
            </div>
        </footer>
    </div>

</body>
</html>
