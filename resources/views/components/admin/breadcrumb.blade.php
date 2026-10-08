@props([
    'items' => []
])

@if(count($items) > 0)
    <nav class="flex items-center text-sm text-slate-500 font-medium space-x-2 overflow-x-auto py-1" aria-label="Breadcrumb">
        <a href="/admin" class="inline-flex items-center hover:text-slate-900 transition-colors">
            <x-icon name="layout-dashboard" class="w-4 h-4 mr-1.5 text-slate-400" />
            <span>儀表板</span>
        </a>

        @foreach($items as $item)
            <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 flex-shrink-0" />
            @if(isset($item['url']) && !$loop->last)
                <a href="{{ $item['url'] }}" class="hover:text-slate-900 transition-colors whitespace-nowrap">
                    {{ $item['label'] }}
                </a>
            @else
                <span class="text-slate-800 font-semibold whitespace-nowrap">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
