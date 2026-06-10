@props(['title' => null, 'padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-2xl border border-line shadow-sm']) }}>
    @if ($title)
        <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <h3 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title }}</h3>
            {{ $action ?? '' }}
        </div>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
