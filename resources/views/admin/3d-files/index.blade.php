@extends('layouts.admin')

@section('title', '3D 檔案管理列表')

@section('breadcrumb')
    <nav class="flex items-center space-x-2 text-xs font-medium text-slate-500">
        <a href="{{ route('admin.3d-files.index') }}" class="hover:text-indigo-600 transition-colors">控制台</a>
        <x-icon name="chevron-right" class="w-3.5 h-3.5 text-slate-400" />
        <span class="text-slate-800 font-semibold">3D 檔案管理</span>
    </nav>
@endsection

@section('header')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">3D 檔案管理列表</h1>
            <p class="text-sm text-slate-500 mt-1">管理並檢視模型檔案解析狀態、尺寸數據與實體檔案下載。</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="{
        deleteModalOpen: false,
        deleteTargetId: null,
        deleteTargetName: '',
        isLoading: false,
        confirmDelete(id, name) {
            this.deleteTargetId = id;
            this.deleteTargetName = name;
            this.deleteModalOpen = true;
        },
        submitDelete() {
            this.isLoading = true;
            document.getElementById('delete-form-' + this.deleteTargetId).submit();
        }
    }">

        <!-- Error State Alert -->
        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start space-x-3">
                <x-icon name="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <span class="font-semibold block">操作失敗</span>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Filter & Search Toolbar -->
        <div class="mb-6 bg-white p-4 md:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <form method="GET" action="{{ route('admin.3d-files.index') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                <!-- Search Box -->
                <div class="relative flex-1 min-w-[240px]">
                    <x-icon name="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="搜尋檔名 Filename..."
                           class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-xl transition-all outline-none text-slate-800 placeholder:text-slate-400">
                </div>

                <!-- Filters Group -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- File Type Filter -->
                    <div class="flex items-center space-x-1.5 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60">
                        <span class="text-xs font-semibold text-slate-500 px-2">類型:</span>
                        <label class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium cursor-pointer transition-colors has-checked:bg-white has-checked:text-indigo-700 has-checked:shadow-2xs text-slate-600">
                            <input type="checkbox" name="type[]" value="stl" class="sr-only" {{ in_array('stl', (array) request('type', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                            STL
                        </label>
                        <label class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium cursor-pointer transition-colors has-checked:bg-white has-checked:text-indigo-700 has-checked:shadow-2xs text-slate-600">
                            <input type="checkbox" name="type[]" value="3mf" class="sr-only" {{ in_array('3mf', (array) request('type', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                            3MF
                        </label>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex flex-wrap items-center gap-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60">
                        <span class="text-xs font-semibold text-slate-500 px-2">狀態:</span>
                        <label class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium cursor-pointer transition-colors has-checked:bg-white has-checked:text-emerald-700 has-checked:shadow-2xs text-slate-600">
                            <input type="checkbox" name="status[]" value="completed" class="sr-only" {{ in_array('completed', (array) request('status', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                            Completed
                        </label>
                        <label class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium cursor-pointer transition-colors has-checked:bg-white has-checked:text-amber-700 has-checked:shadow-2xs text-slate-600">
                            <input type="checkbox" name="status[]" value="processing" class="sr-only" {{ in_array('processing', (array) request('status', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                            Processing
                        </label>
                        <label class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium cursor-pointer transition-colors has-checked:bg-white has-checked:text-rose-700 has-checked:shadow-2xs text-slate-600">
                            <input type="checkbox" name="status[]" value="failed" class="sr-only" {{ in_array('failed', (array) request('status', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                            Failed
                        </label>
                    </div>

                    <!-- Sort By Selector -->
                    <div class="flex items-center space-x-2">
                        <select name="sort_by" onchange="this.form.submit()" class="py-2 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:outline-none focus:border-indigo-500">
                            <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>排序: 建立時間</option>
                            <option value="original_filename" {{ request('sort_by') === 'original_filename' ? 'selected' : '' }}>排序: 檔案名稱</option>
                            <option value="file_size" {{ request('sort_by') === 'file_size' ? 'selected' : '' }}>排序: 檔案大小</option>
                            <option value="status" {{ request('sort_by') === 'status' ? 'selected' : '' }}>排序: 解析狀態</option>
                        </select>

                        <select name="sort_dir" onchange="this.form.submit()" class="py-2 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:outline-none focus:border-indigo-500">
                            <option value="desc" {{ request('sort_dir', 'desc') === 'desc' ? 'selected' : '' }}>遞減 ↓</option>
                            <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>遞增 ↑</option>
                        </select>
                    </div>

                    @if(request()->hasAny(['search', 'type', 'status', 'sort_by', 'sort_dir']))
                        <a href="{{ route('admin.3d-files.index') }}" class="p-2 text-xs text-slate-500 hover:text-slate-800 transition-colors inline-flex items-center">
                            <x-icon name="rotate-ccw" class="w-3.5 h-3.5 mr-1" /> 重設
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Loading Overlay Indicator (Alpine managed) -->
        <div x-show="isLoading" class="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-xs flex items-center justify-center" x-cloak>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex items-center space-x-4 border border-slate-100">
                <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">處理中，請稍候...</span>
            </div>
        </div>

        <!-- Empty State View -->
        @if($modelFiles->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center my-6">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <x-icon name="box" class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">找不到相關 3D 檔案紀錄</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">目前系統中尚未上傳任何模型檔案，或符合您篩選條件的資料不存在。</p>
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('admin.3d-files.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition-colors">
                        清除所有搜尋條件
                    </a>
                @endif
            </div>
        @else

            <!-- Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4 text-center w-20">Preview</th>
                            <th class="py-3.5 px-4">Filename</th>
                            <th class="py-3.5 px-3">Type</th>
                            <th class="py-3.5 px-3">File Size</th>
                            <th class="py-3.5 px-4">Dimensions</th>
                            <th class="py-3.5 px-3">Status</th>
                            <th class="py-3.5 px-4">Parsed At</th>
                            <th class="py-3.5 px-4">Created At</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700 font-medium">
                        @foreach($modelFiles as $file)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- Preview -->
                                <td class="py-3 px-4 text-center">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-500 mx-auto">
                                        @if(strtoupper($file->extension) === '3MF')
                                            <x-icon name="layers" class="w-5 h-5 text-indigo-500" />
                                        @else
                                            <x-icon name="box" class="w-5 h-5 text-indigo-500" />
                                        @endif
                                    </div>
                                </td>

                                <!-- Filename -->
                                <td class="py-3 px-4">
                                    <a href="{{ route('admin.3d-files.show', $file->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors line-clamp-1" title="{{ $file->original_filename }}">
                                        {{ $file->original_filename }}
                                    </a>
                                </td>

                                <!-- Type -->
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase {{ strtoupper($file->extension) === '3MF' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ strtoupper($file->extension) }}
                                    </span>
                                </td>

                                <!-- File Size -->
                                <td class="py-3 px-3 text-slate-600">
                                    {{ number_format($file->file_size / 1024, 2) }} KB
                                </td>

                                <!-- Dimensions -->
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    @if($file->width && $file->height && $file->depth)
                                        {{ number_format($file->width, 1) }} × {{ number_format($file->height, 1) }} × {{ number_format($file->depth, 1) }} mm
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-3">
                                    @if($file->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                            Completed
                                        </span>
                                    @elseif(in_array($file->status, ['processing', 'pending']))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>
                                            Processing
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                            Failed
                                        </span>
                                    @endif
                                </td>

                                <!-- Parsed At -->
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                    {{ $file->parsed_at ? $file->parsed_at->format('Y-m-d H:i') : '-' }}
                                </td>

                                <!-- Created At -->
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                    {{ $file->created_at ? $file->created_at->format('Y-m-d H:i') : '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('admin.3d-files.show', $file->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors" title="View Detail">
                                        <x-icon name="eye" class="w-4 h-4 mr-1" />
                                        View
                                    </a>
                                    <a href="{{ route('admin.3d-files.download', $file->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 font-semibold transition-colors" title="Download File">
                                        <x-icon name="download" class="w-4 h-4 mr-1" />
                                        Download
                                    </a>
                                    <button @click="confirmDelete('{{ $file->id }}', '{{ addslashes($file->original_filename) }}')" type="button" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-rose-50 font-semibold transition-colors" title="Delete Record">
                                        <x-icon name="trash-2" class="w-4 h-4 mr-1" />
                                        Delete
                                    </button>

                                    <!-- Hidden form for deletion -->
                                    <form id="delete-form-{{ $file->id }}" method="POST" action="{{ route('admin.3d-files.destroy', $file->id) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Shown on mobile screens) -->
            <div class="block md:hidden space-y-4">
                @foreach($modelFiles as $file)
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-500 flex-shrink-0">
                                    @if(strtoupper($file->extension) === '3MF')
                                        <x-icon name="layers" class="w-5 h-5 text-indigo-500" />
                                    @else
                                        <x-icon name="box" class="w-5 h-5 text-indigo-500" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.3d-files.show', $file->id) }}" class="font-bold text-sm text-slate-900 hover:text-indigo-600 transition-colors line-clamp-1">
                                        {{ $file->original_filename }}
                                    </a>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ strtoupper($file->extension) === '3MF' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                            {{ strtoupper($file->extension) }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ number_format($file->file_size / 1024, 2) }} KB
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div>
                                @if($file->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Completed
                                    </span>
                                @elseif(in_array($file->status, ['processing', 'pending']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Processing
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Failed
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Details -->
                        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-slate-600">
                            <div>
                                <span class="text-slate-400 block text-[10px]">Dimensions:</span>
                                <span class="font-medium text-slate-800">
                                    @if($file->width && $file->height && $file->depth)
                                        {{ number_format($file->width, 1) }} × {{ number_format($file->height, 1) }} × {{ number_format($file->depth, 1) }} mm
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Created At:</span>
                                <span class="font-medium text-slate-800">
                                    {{ $file->created_at ? $file->created_at->format('Y-m-d H:i') : '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <a href="{{ route('admin.3d-files.show', $file->id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                View
                            </a>
                            <a href="{{ route('admin.3d-files.download', $file->id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                Download
                            </a>
                            <button @click="confirmDelete('{{ $file->id }}', '{{ addslashes($file->original_filename) }}')" type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-6">
                {{ $modelFiles->links() }}
            </div>
        @endif

        <!-- Delete Confirmation Dialog Modal -->
        <div x-show="deleteModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             x-cloak>

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="deleteModalOpen = false"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div x-show="deleteModalOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-2xl transition-all border border-slate-100">

                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
                        <x-icon name="trash-2" class="w-6 h-6" />
                    </div>

                    <h3 class="text-lg font-bold text-slate-900">確定要刪除此 3D 檔案嗎？</h3>
                    <p class="text-xs text-slate-500 mt-2">
                        您正在刪除檔案：<strong class="text-slate-800 break-all" x-text="deleteTargetName"></strong><br>
                        此動作將會永久刪除資料庫紀錄與實體檔案，且無法復原。
                    </p>

                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                            取消
                        </button>
                        <button type="button" @click="submitDelete()" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors shadow-xs">
                            確認刪除
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
