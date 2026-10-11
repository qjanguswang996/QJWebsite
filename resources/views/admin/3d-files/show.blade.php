@extends('layouts.admin')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">3D 檔案解析詳情</h1>
            <p class="text-xs text-slate-500 mt-1">檢視 3D 模型檔案詳細解析數據與系統狀態</p>
        </div>
        <a href="/admin" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
            返回控制台
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        <!-- 3D Viewer Container Card -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden relative">
            <!-- Viewer Top Bar Overlay -->
            <div class="absolute top-0 inset-x-0 z-10 p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-b from-slate-950/80 via-slate-950/40 to-transparent backdrop-blur-xs pointer-events-none">

                <!-- Left Top: File Name & Status -->
                <div class="flex items-center space-x-3 pointer-events-auto">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                        <x-icon name="box" class="w-4 h-4" />
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span id="viewer-file-name" class="text-sm font-bold text-white truncate max-w-[200px] sm:max-w-xs" title="{{ $modelFile->original_filename }}">
                            {{ $modelFile->original_filename }}
                        </span>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <span id="viewer-status-badge" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                <span id="viewer-status">準備載入...</span>
                            </span>
                            <span class="text-[11px] font-mono text-slate-400 uppercase">
                                {{ $modelFile->extension }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Top: Interactive Controls -->
                <div class="flex items-center space-wrap gap-1.5 pointer-events-auto self-end sm:self-auto">
                    <button id="btn-viewer-reset" type="button" title="Reset Camera"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                        <x-icon name="rotate-ccw" class="w-3.5 h-3.5 text-indigo-400" />
                        <span class="hidden sm:inline">Reset</span>
                    </button>

                    <button id="btn-viewer-fit" type="button" title="Fit Model"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                        <x-icon name="maximize-2" class="w-3.5 h-3.5 text-emerald-400" />
                        <span class="hidden sm:inline">Fit</span>
                    </button>

                    <button id="btn-viewer-wireframe" type="button" title="Wireframe Toggle"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                        <x-icon name="grid" class="w-3.5 h-3.5 text-amber-400" />
                        <span class="hidden sm:inline">Wireframe</span>
                    </button>

                    <button id="btn-viewer-autorotate" type="button" title="Auto Rotate Toggle"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all flex items-center space-x-1.5 focus:outline-none">
                        <x-icon name="refresh-cw" class="w-3.5 h-3.5 text-cyan-400" />
                        <span class="hidden sm:inline">Auto Rotate</span>
                    </button>

                    <button id="btn-viewer-visibility" type="button" title="顯示 / 隱藏模型"
                            class="p-1.5 rounded-lg text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700/60 shadow-xs transition-all focus:outline-none">
                        <x-icon name="eye" class="w-4 h-4 text-slate-300" />
                    </button>
                </div>
            </div>

            <!-- Canvas Container -->
            <div id="three-viewer-container"
                 class="w-full h-[420px] sm:h-[500px] md:h-[560px] bg-slate-900 overflow-hidden relative cursor-grab active:cursor-grabbing"
                 data-file-url="{{ route('admin.3d-files.file', $modelFile->id) }}"
                 data-file-type="{{ $modelFile->extension }}"
                 data-file-name="{{ $modelFile->original_filename }}">
            </div>

            <!-- Bottom Bar Overlay: Control Instructions -->
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
                    <span class="hidden md:inline-flex items-center space-x-1 text-slate-500 border-l border-slate-800 pl-4">
                        <span class="font-medium text-slate-300">📱 觸控：</span>
                        <span>單指旋轉 ｜ 雙指平移與縮放</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Info Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-6 mb-6">
                <div>
                    <div class="flex items-center space-x-3">
                        <span id="detail-filename" class="text-xl font-bold text-slate-800">{{ $modelFile->original_filename }}</span>
                        <span id="detail-extension" class="px-2.5 py-1 text-xs font-bold rounded-lg uppercase tracking-wide bg-slate-100 text-slate-600">
                            {{ $modelFile->extension }}
                        </span>
                    </div>
                    <p id="detail-stored-filename" class="text-xs text-slate-400 mt-1 font-mono">系統檔名: {{ $modelFile->stored_filename }}</p>
                </div>

                <!-- Status Badge -->
                <div id="detail-status-badge">
                    @if ($modelFile->status === 'completed')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                            Completed
                        </span>
                    @elseif ($modelFile->status === 'processing')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 animate-pulse">
                            <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span>
                            Processing
                        </span>
                    @elseif ($modelFile->status === 'failed')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20">
                            <span class="w-2 h-2 rounded-full bg-rose-500 mr-2"></span>
                            Failed
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 ring-1 ring-slate-400/20">
                            <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>
                            Pending
                        </span>
                    @endif
                </div>
            </div>

            @if ($modelFile->status === 'failed' && $modelFile->error_message)
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                    <p class="font-bold">解析錯誤訊息：</p>
                    <p id="detail-error-message" class="mt-1 font-mono text-xs">{{ $modelFile->error_message }}</p>
                </div>
            @endif

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Filename & File Info -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Filename</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1 truncate" title="{{ $modelFile->original_filename }}">
                        {{ $modelFile->original_filename }}
                    </p>
                </div>

                <!-- File Size -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">File size</p>
                    <p id="detail-file-size" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ number_format($modelFile->file_size) }} Bytes ({{ round($modelFile->file_size / 1024, 2) }} KB)
                    </p>
                </div>

                <!-- File Type -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">File type</p>
                    <p id="detail-file-type" class="text-sm font-semibold text-slate-800 mt-1 uppercase">
                        {{ $modelFile->extension }} @if($modelFile->mime_type) <span class="text-xs text-slate-400 lowercase font-normal">({{ $modelFile->mime_type }})</span> @endif
                    </p>
                </div>

                <!-- Status -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Status</p>
                    <p id="detail-status-text" class="text-sm font-semibold text-slate-800 mt-1 capitalize">
                        {{ $modelFile->status }}
                    </p>
                </div>

                <!-- Processing Time -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Processing time</p>
                    <p id="detail-processing-time" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ $processingTime ?? ($modelFile->parsed_at ? $modelFile->parsed_at->format('Y-m-d H:i:s') : '尚未完成') }}
                    </p>
                </div>

                <!-- Dimensions -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Dimensions (W × H × D)</p>
                    <p id="detail-dimensions" class="text-sm font-semibold text-slate-800 mt-1">
                        @if ($modelFile->width !== null)
                            {{ $modelFile->width }} × {{ $modelFile->height }} × {{ $modelFile->depth }} mm
                        @else
                            N/A
                        @endif
                    </p>
                </div>

                <!-- Vertex Count -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Vertex count</p>
                    <p id="detail-vertex-count" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ $modelFile->vertex_count !== null ? number_format($modelFile->vertex_count) : 'N/A' }}
                    </p>
                </div>

                <!-- Triangle Count -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Triangle count</p>
                    <p id="detail-triangle-count" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ $modelFile->triangle_count !== null ? number_format($modelFile->triangle_count) : 'N/A' }}
                    </p>
                </div>

                <!-- Volume -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-xs text-slate-400 font-medium">Volume</p>
                    <p id="detail-volume" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ $modelFile->volume !== null ? number_format($modelFile->volume, 3) . ' cm³' : 'N/A' }}
                    </p>
                </div>

                <!-- Surface Area -->
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100 lg:col-span-3">
                    <p class="text-xs text-slate-400 font-medium">Surface area</p>
                    <p id="detail-surface-area" class="text-sm font-semibold text-slate-800 mt-1">
                        {{ $modelFile->surface_area !== null ? number_format($modelFile->surface_area, 3) . ' cm²' : 'N/A' }}
                    </p>
                </div>

            </div>
        </div>
    </div>
@endsection
