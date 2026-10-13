@extends('layouts.admin')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">3D 模型詳情</h1>
            <p class="text-xs text-slate-500 mt-1">檢視 3D 模型幾何數據、邊界框與檔案資訊</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.3d-files.download', $modelFile->id) }}" class="inline-flex items-center space-x-2 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors">
                <x-icon name="download" class="w-4 h-4" />
                <span>下載原始檔案</span>
            </a>
            <a href="/admin" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                返回控制台
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Desktop: Left 3D Viewer, Right Parameters / Mobile: Stacked -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- 第一區：3D Viewer (Desktop Left, Mobile Top) -->
        <div class="lg:col-span-7 xl:col-span-7 space-y-6">
            <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden relative">

                @if (in_array($modelFile->status, ['pending', 'processing']))
                    <!-- Processing State Viewer -->
                    <div class="w-full h-[420px] sm:h-[500px] lg:h-[600px] flex flex-col items-center justify-center p-6 text-center bg-slate-900 text-slate-300">
                        <div class="relative flex items-center justify-center mb-4">
                            <div class="w-16 h-16 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div>
                            <x-icon name="box" class="w-6 h-6 text-indigo-400 absolute" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1">檔案解析處理中...</h3>
                        <p class="text-xs text-slate-400 max-w-sm">
                            模型目前狀態為 <span class="text-amber-400 font-semibold uppercase">{{ $modelFile->status }}</span>，系統正在後台計算網格數據與幾何參數。
                        </p>
                    </div>

                @elseif ($modelFile->status === 'failed')
                    <!-- Error State Viewer -->
                    <div class="w-full h-[420px] sm:h-[500px] lg:h-[600px] flex flex-col items-center justify-center p-6 text-center bg-slate-900 text-slate-300">
                        <div class="w-16 h-16 rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center mb-4">
                            <x-icon name="alert-triangle" class="w-8 h-8" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1">模型解析失敗</h3>
                        <p class="text-xs text-slate-400 max-w-md mb-3">
                            無法正常解析此 3D 檔案網格結構，請確認檔案格式是否正確或未損壞。
                        </p>
                        @if ($modelFile->error_message)
                            <div class="p-3 bg-rose-950/50 border border-rose-900/60 rounded-xl max-w-md text-left font-mono text-[11px] text-rose-300 break-all">
                                {{ $modelFile->error_message }}
                            </div>
                        @endif
                    </div>

                @else
                    <!-- Completed State: Interactive 3D Viewer -->
                    <div class="absolute top-0 inset-x-0 z-10 p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-b from-slate-950/80 via-slate-950/40 to-transparent backdrop-blur-xs pointer-events-none">

                        <!-- Header Info -->
                        <div class="flex items-center space-x-3 pointer-events-auto">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                                <x-icon name="box" class="w-4 h-4" />
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span id="viewer-file-name" class="text-sm font-bold text-white truncate max-w-[180px] sm:max-w-xs" title="{{ $modelFile->original_filename }}">
                                    {{ $modelFile->original_filename }}
                                </span>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span id="viewer-status-badge" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        <span id="viewer-status">已就緒</span>
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400 uppercase">
                                        {{ $modelFile->extension }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Viewer Interactive Controls Toolbar -->
                        <div class="flex items-center space-wrap gap-1.5 pointer-events-auto self-end sm:self-auto">
                            <button id="btn-viewer-reset" type="button" title="重置視角"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                                <x-icon name="rotate-ccw" class="w-3.5 h-3.5 text-indigo-400" />
                                <span class="hidden sm:inline">重置</span>
                            </button>

                            <button id="btn-viewer-fit" type="button" title="適應大小"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                                <x-icon name="maximize-2" class="w-3.5 h-3.5 text-emerald-400" />
                                <span class="hidden sm:inline">對齊</span>
                            </button>

                            <button id="btn-viewer-wireframe" type="button" title="線框模式"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                                <x-icon name="grid" class="w-3.5 h-3.5 text-amber-400" />
                                <span class="hidden sm:inline">線框</span>
                            </button>

                            <button id="btn-viewer-autorotate" type="button" title="自動旋轉"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                                <x-icon name="refresh-cw" class="w-3.5 h-3.5 text-cyan-400" />
                                <span class="hidden sm:inline">旋轉</span>
                            </button>

                            <button id="btn-viewer-visibility" type="button" title="顯示 / 隱藏模型"
                                    class="p-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all focus:outline-none">
                                <x-icon name="eye" class="w-4 h-4 text-slate-300" />
                            </button>
                        </div>
                    </div>

                    <!-- WebGL Canvas Container -->
                    <div id="three-viewer-container"
                         class="w-full h-[420px] sm:h-[500px] lg:h-[600px] bg-slate-900 overflow-hidden relative cursor-grab active:cursor-grabbing"
                         data-file-url="{{ route('admin.3d-files.file', $modelFile->id) }}"
                         data-file-type="{{ $modelFile->extension }}"
                         data-file-name="{{ $modelFile->original_filename }}">
                    </div>

                    <!-- Viewer Bottom Helper Bar -->
                    <div class="absolute bottom-0 inset-x-0 z-10 py-2.5 px-4 bg-gradient-to-t from-slate-950/90 via-slate-950/50 to-transparent pointer-events-none flex items-center justify-between text-[11px] text-slate-400">
                        <div class="flex items-center space-x-4 mx-auto sm:mx-0">
                            <span class="hidden sm:inline-flex items-center space-x-1">
                                <span class="font-medium text-slate-300">🖱️ 滑鼠操作：</span>
                                <span>左鍵旋轉 ｜ 右鍵平移 ｜ 滾輪縮放</span>
                            </span>
                            <span class="inline-flex sm:hidden items-center space-x-1">
                                <span class="font-medium text-slate-300">📱 觸控操作：</span>
                                <span>單指旋轉 ｜ 雙指平移與縮放</span>
                            </span>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <!-- Desktop Right Parameters Column / Mobile Bottom Stack -->
        <div class="lg:col-span-5 xl:col-span-5 space-y-6">

            <!-- 第二區：Model Information -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 flex items-center space-x-2">
                        <x-icon name="info" class="w-4 h-4 text-indigo-500" />
                        <span>Model Information</span>
                    </h2>

                    <!-- Download Link Button -->
                    <a href="{{ route('admin.3d-files.download', $modelFile->id) }}"
                       title="下載原始 3D 檔案"
                       class="inline-flex items-center space-x-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors">
                        <x-icon name="download" class="w-3.5 h-3.5" />
                        <span>下載檔案</span>
                    </a>
                </div>

                <div class="space-y-3 text-sm">
                    <!-- Filename -->
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500 text-xs font-medium">Filename</span>
                        <span class="font-semibold text-slate-800 truncate max-w-[200px]" title="{{ $modelFile->original_filename }}">
                            {{ $modelFile->original_filename ?? '-' }}
                        </span>
                    </div>

                    <!-- File type -->
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500 text-xs font-medium">File type</span>
                        <span class="font-semibold text-slate-800 uppercase text-xs px-2 py-0.5 rounded bg-slate-100">
                            {{ $modelFile->extension ? strtoupper($modelFile->extension) : '-' }}
                            @if($modelFile->mime_type)
                                <span class="lowercase text-[11px] text-slate-400 font-normal">({{ $modelFile->mime_type }})</span>
                            @endif
                        </span>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500 text-xs font-medium">Status</span>
                        <div>
                            @if ($modelFile->status === 'completed')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Completed
                                </span>
                            @elseif ($modelFile->status === 'processing')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 animate-pulse">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    Processing
                                </span>
                            @elseif ($modelFile->status === 'failed')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                    Failed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 ring-1 ring-slate-400/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                    Pending
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Parsed time -->
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-500 text-xs font-medium">Parsed time</span>
                        <span class="font-semibold text-slate-800 text-xs">
                            @if ($modelFile->parsed_at)
                                {{ $modelFile->parsed_at->format('Y-m-d H:i:s') }}
                                @if ($processingTime)
                                    <span class="text-[11px] text-slate-400 font-normal">({{ $processingTime }})</span>
                                @endif
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- 第三區：Geometry Parameters -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 flex items-center space-x-2">
                        <x-icon name="activity" class="w-4 h-4 text-emerald-500" />
                        <span>Geometry Parameters</span>
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 text-sm">
                    <!-- Width -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Width</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->width !== null ? number_format($modelFile->width, 2) . ' mm' : '-' }}
                        </span>
                    </div>

                    <!-- Height -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Height</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->height !== null ? number_format($modelFile->height, 2) . ' mm' : '-' }}
                        </span>
                    </div>

                    <!-- Depth -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Depth</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->depth !== null ? number_format($modelFile->depth, 2) . ' mm' : '-' }}
                        </span>
                    </div>

                    <!-- Volume -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Volume</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->volume !== null ? number_format($modelFile->volume, 3) . ' cm³' : '-' }}
                        </span>
                    </div>

                    <!-- Surface Area -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Surface Area</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->surface_area !== null ? number_format($modelFile->surface_area, 3) . ' cm²' : '-' }}
                        </span>
                    </div>

                    <!-- Vertex Count -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block">Vertex Count</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->vertex_count !== null ? number_format($modelFile->vertex_count) : '-' }}
                        </span>
                    </div>

                    <!-- Triangle Count -->
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 col-span-2">
                        <span class="text-xs text-slate-400 font-medium block">Triangle Count</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            {{ $modelFile->triangle_count !== null ? number_format($modelFile->triangle_count) : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 第四區：File Information / Bounding Box & Center -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-5">

                <!-- Bounding Box -->
                <div>
                    <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <x-icon name="maximize-2" class="w-4 h-4 text-cyan-500" />
                            <span>Bounding Box</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Min X</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->min_x !== null ? number_format($modelFile->min_x, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Min Y</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->min_y !== null ? number_format($modelFile->min_y, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Min Z</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->min_z !== null ? number_format($modelFile->min_z, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Max X</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->max_x !== null ? number_format($modelFile->max_x, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Max Y</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->max_y !== null ? number_format($modelFile->max_y, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Max Z</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->max_z !== null ? number_format($modelFile->max_z, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Center -->
                <div>
                    <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <x-icon name="target" class="w-4 h-4 text-amber-500" />
                            <span>Center Coordinates</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">X</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->center_x !== null ? number_format($modelFile->center_x, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Y</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->center_y !== null ? number_format($modelFile->center_y, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-slate-400 font-medium block text-[10px]">Z</span>
                            <span class="font-mono font-bold text-slate-700 mt-0.5 block">
                                {{ $modelFile->center_z !== null ? number_format($modelFile->center_z, 2) . ' mm' : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- File Information -->
                <div>
                    <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <x-icon name="file-text" class="w-4 h-4 text-purple-500" />
                            <span>File Information</span>
                        </h2>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-500">File size</span>
                            <span class="font-semibold text-slate-800">
                                @if ($modelFile->file_size !== null)
                                    {{ number_format($modelFile->file_size) }} Bytes
                                    <span class="text-[11px] text-slate-400 font-normal">({{ round($modelFile->file_size / 1024, 2) }} KB)</span>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-500">Stored filename</span>
                            <span class="font-mono text-slate-600 truncate max-w-[180px]" title="{{ $modelFile->stored_filename }}">
                                {{ $modelFile->stored_filename ?? '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-500">Uploaded at</span>
                            <span class="text-slate-700">
                                {{ $modelFile->created_at ? $modelFile->created_at->format('Y-m-d H:i:s') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
@endsection
