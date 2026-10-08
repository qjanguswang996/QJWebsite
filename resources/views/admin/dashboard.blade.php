@extends('layouts.admin')

@section('title', 'Dashboard - 儀表板')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => '控制台儀表板']
    ]" />
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">儀表板</h1>
            <p class="text-sm text-slate-500 mt-1">歡迎回來！以下是 3D 檔案解析系統的總覽數據與狀態。</p>
        </div>

        <!-- Interactive UI State Demo Selector -->
        <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl text-xs font-medium self-start sm:self-auto">
            <a href="{{ route('admin.dashboard', ['demo_state' => 'normal']) }}"
               class="px-3 py-1.5 rounded-lg transition-all {{ $activeDemoState === 'normal' ? 'bg-white text-indigo-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                一般檢視
            </a>
            <a href="{{ route('admin.dashboard', ['demo_state' => 'loading']) }}"
               class="px-3 py-1.5 rounded-lg transition-all {{ $activeDemoState === 'loading' ? 'bg-white text-indigo-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Loading State
            </a>
            <a href="{{ route('admin.dashboard', ['demo_state' => 'empty']) }}"
               class="px-3 py-1.5 rounded-lg transition-all {{ $activeDemoState === 'empty' ? 'bg-white text-indigo-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Empty State
            </a>
            <a href="{{ route('admin.dashboard', ['demo_state' => 'error']) }}"
               class="px-3 py-1.5 rounded-lg transition-all {{ $activeDemoState === 'error' ? 'bg-white text-indigo-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Error State
            </a>
        </div>
    </div>
@endsection

@section('content')

    @if($activeDemoState === 'loading')
        <!-- Demo Loading State -->
        <div class="space-y-6">
            <x-admin.loading-state text="正在讀取 3D 解析與系統數據..." :rows="4" />
        </div>

    @elseif($activeDemoState === 'error')
        <!-- Demo Error State -->
        <div class="space-y-6">
            <x-admin.error-state
                title="無法載入 3D 數據儀表板"
                message="資料庫連線逾時或 3D 解析服務回應異常，請確認後端服務運作正常。"
                :retryUrl="route('admin.dashboard')" />
        </div>

    @else
        <!-- Standard Content Display -->
        <div class="space-y-8">

            <!-- 1. 3D 解析數量 (Metrics Cards) -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <!-- Card 1: Total Parsed -->
                <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-xs hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">總解析數量</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-icon name="box" class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalFiles) }}</span>
                        <span class="inline-flex items-center text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            <x-icon name="trending-up" class="w-3 h-3 mr-1" />
                            即時
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">系統累計解析的模型數量</p>
                </div>

                <!-- Card 2: STL Files -->
                <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-xs hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">STL 格式檔案</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <x-icon name="cube" class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($stlCount) }}</span>
                        <span class="text-xs font-medium text-slate-500">.stl</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Standard Tessellation Language</p>
                </div>

                <!-- Card 3: 3MF Files -->
                <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-xs hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">3MF 格式檔案</span>
                        <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                            <x-icon name="layers" class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($threeMfCount) }}</span>
                        <span class="text-xs font-medium text-slate-500">.3mf</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">3D Manufacturing Format</p>
                </div>

                <!-- Card 4: Total File Size -->
                <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-xs hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">總檔案容量</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <x-icon name="hard-drive" class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $formattedTotalSize }}</span>
                        <span class="text-xs font-medium text-emerald-600">已使用</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">本地 Storage 儲存總量</p>
                </div>
            </section>

            <!-- 2. 最近解析檔案與系統狀態 Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- 最近解析檔案 (Recent Parsed Files Table / Card) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 tracking-tight">最近解析檔案</h2>
                            <p class="text-xs text-slate-500">最近上傳並完成 3D 解析的模型列表</p>
                        </div>
                        <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors inline-flex items-center">
                            查看全部
                            <x-icon name="chevron-right" class="w-3.5 h-3.5 ml-1" />
                        </a>
                    </div>

                    @if($activeDemoState === 'empty' || $recentFiles->isEmpty())
                        <!-- Empty State when database has no records or in empty demo mode -->
                        <x-admin.empty-state
                            title="尚無 3D 解析檔案"
                            description="目前資料庫中尚未建立任何 3D 檔案解析紀錄。您可以在下一步上傳 STL 或 3MF 模型進行解析。"
                            icon="folder-open"
                            actionUrl="#"
                            actionLabel="上傳 3D 模型" />
                    @else
                        <!-- Data Table -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50/80 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                        <tr>
                                            <th class="px-5 py-3.5">檔案名稱</th>
                                            <th class="px-5 py-3.5">格式</th>
                                            <th class="px-5 py-3.5">大小</th>
                                            <th class="px-5 py-3.5">狀態</th>
                                            <th class="px-5 py-3.5">解析時間</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($recentFiles as $file)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-5 py-4 font-medium text-slate-800 flex items-center space-x-3">
                                                    <div class="w-8 h-8 rounded-lg {{ $file->format === 'STL' ? 'bg-blue-50 text-blue-600' : 'bg-violet-50 text-violet-600' }} flex items-center justify-center flex-shrink-0">
                                                        <x-icon name="box" class="w-4 h-4" />
                                                    </div>
                                                    <span class="truncate max-w-xs" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $file->format === 'STL' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-violet-50 text-violet-700 border border-violet-100' }}">
                                                        {{ $file->format }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-slate-500 text-xs">
                                                    {{ $file->formatted_size }}
                                                </td>
                                                <td class="px-5 py-4">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                                        {{ $file->status === 'completed' ? '解析完成' : $file->status }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-slate-400 text-xs">
                                                    {{ $file->parsed_at ? $file->parsed_at->diffForHumans() : $file->created_at->diffForHumans() }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 3. 系統狀態 (System Status Card) -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight">系統狀態</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            運作正常
                        </span>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-5 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                                    <x-icon name="cpu" class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">PHP 版本</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ $systemStatus['php_version'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-emerald-600 font-medium">PHP 8.3+ OK</span>
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                                    <x-icon name="database" class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">資料庫狀態</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ $systemStatus['database_status'] }}</p>
                                </div>
                            </div>
                            <x-icon name="check-circle-2" class="w-4 h-4 text-emerald-500" />
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                                    <x-icon name="hard-drive" class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Storage 驅動</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ $systemStatus['storage_driver'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-slate-500">Local</span>
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                                    <x-icon name="activity" class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Queue 隊列驅動</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ $systemStatus['queue_connection'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-slate-500">Sync</span>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <x-icon name="sparkles" class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">3D 解析引擎 (Three.js)</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ $systemStatus['webgl_status'] }}</p>
                                </div>
                            </div>
                            <x-icon name="check-circle-2" class="w-4 h-4 text-emerald-500" />
                        </div>
                    </div>
                </div>

            </div>

        </div>
    @endif

@endsection
