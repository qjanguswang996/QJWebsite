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
