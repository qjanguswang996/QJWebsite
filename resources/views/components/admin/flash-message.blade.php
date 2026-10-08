@props([
    'type' => session('status_type', 'success'),
    'message' => session('status') ?? session('message')
])

@if($message)
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="mb-6 rounded-xl p-4 border flex items-start space-x-3 shadow-xs transition-all {{ match($type) {
             'error', 'danger' => 'bg-red-50/80 border-red-200 text-red-800',
             'warning' => 'bg-amber-50/80 border-amber-200 text-amber-800',
             'info' => 'bg-blue-50/80 border-blue-200 text-blue-800',
             default => 'bg-emerald-50/80 border-emerald-200 text-emerald-800',
         } }}">

        <div class="flex-shrink-0 mt-0.5">
            @switch($type)
                @case('error')
                @case('danger')
                    <x-icon name="alert-circle" class="w-5 h-5 text-red-500" />
                    @break
                @case('warning')
                    <x-icon name="alert-triangle" class="w-5 h-5 text-amber-500" />
                    @break
                @case('info')
                    <x-icon name="info" class="w-5 h-5 text-blue-500" />
                    @break
                @default
                    <x-icon name="check-circle-2" class="w-5 h-5 text-emerald-500" />
            @endswitch
        </div>

        <div class="flex-1 text-sm font-medium">
            {{ $message }}
        </div>

        <button @click="show = false" type="button" class="flex-shrink-0 text-slate-400 hover:text-slate-600 rounded-lg p-1 transition-colors">
            <x-icon name="x" class="w-4 h-4" />
        </button>
    </div>
@endif
