@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
