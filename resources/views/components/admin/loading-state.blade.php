@props([
    'text' => '資料載入中...',
    'rows' => 3
])

<div class="p-8 rounded-2xl bg-white border border-slate-100 shadow-xs flex flex-col items-center justify-center text-center">
    <div class="relative flex items-center justify-center mb-4">
        <x-icon name="loader-2" class="w-8 h-8 text-indigo-600 animate-spin" />
    </div>
    <p class="text-sm font-medium text-slate-600 mb-6">{{ $text }}</p>

    <div class="w-full max-w-md space-y-3">
        @for ($i = 0; $i < $rows; $i++)
            <div class="h-3 bg-slate-100 rounded-full animate-pulse {{ $i === 0 ? 'w-full' : ($i === 1 ? 'w-5/6 mx-auto' : 'w-2/3 mx-auto') }}"></div>
        @endfor
    </div>
</div>
