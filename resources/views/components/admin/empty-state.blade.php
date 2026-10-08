@props([
    'title' => '尚無資料',
    'description' => '目前系統中沒有任何記錄。請新增項目或等待系統運作。',
    'icon' => 'folder-open',
    'actionUrl' => null,
    'actionLabel' => null
])

<div class="p-12 rounded-2xl bg-white border border-slate-100 shadow-xs flex flex-col items-center justify-center text-center">
    <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100/50 text-indigo-600 flex items-center justify-center mb-4 shadow-xs">
        <x-icon :name="$icon" class="w-8 h-8" />
    </div>

    <h3 class="text-base font-semibold text-slate-800 mb-1">{{ $title }}</h3>
    <p class="text-sm text-slate-500 max-w-sm mb-6 leading-relaxed">{{ $description }}</p>

    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-all shadow-xs hover:shadow-md">
            <x-icon name="plus" class="w-4 h-4 mr-1.5" />
            {{ $actionLabel }}
        </a>
    @endif
</div>
