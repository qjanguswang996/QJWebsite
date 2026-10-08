@props([
    'title' => '系統發生錯誤',
    'message' => '無法載入頁面資料，請稍後再試或聯絡系統管理員。',
    'retryUrl' => null
])

<div class="p-8 rounded-2xl bg-rose-50/50 border border-rose-200/60 shadow-xs flex flex-col items-center justify-center text-center">
    <div class="w-14 h-14 rounded-2xl bg-rose-100/80 text-rose-600 flex items-center justify-center mb-4">
        <x-icon name="alert-triangle" class="w-7 h-7" />
    </div>

    <h3 class="text-base font-semibold text-rose-900 mb-1">{{ $title }}</h3>
    <p class="text-sm text-rose-600 max-w-md mb-6 leading-relaxed">{{ $message }}</p>

    @if($retryUrl)
        <a href="{{ $retryUrl }}" class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-xl transition-all shadow-xs hover:shadow-md">
            <x-icon name="refresh-cw" class="w-4 h-4 mr-1.5" />
            重新嘗試
        </a>
    @else
        <button onclick="window.location.reload()" type="button" class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-xl transition-all shadow-xs hover:shadow-md">
            <x-icon name="refresh-cw" class="w-4 h-4 mr-1.5" />
            重新整理頁面
        </button>
    @endif
</div>
