@props(['title' => 'No data yet', 'description' => null, 'action' => null])

<div class="text-center py-16">
    <div class="w-12 h-12 bg-navy/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>
    <p class="text-sm font-bold text-ink">{{ $title }}</p>
    @if ($description)
        <p class="text-xs text-muted mt-1">{{ $description }}</p>
    @endif
    {{ $slot }}
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
