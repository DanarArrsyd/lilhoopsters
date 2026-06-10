@props(['label' => null, 'error' => null])

@php
    $fieldId = $attributes->get('id') ?? $attributes->get('name');
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label @if($fieldId) for="{{ $fieldId }}" @endif class="block text-xs font-semibold uppercase tracking-wide text-navy">
            {{ $label }}
        </label>
    @endif

    <div class="relative group">
        <select {{ $attributes->merge([
            'id'    => $fieldId,
            'class' => 'appearance-none block w-full rounded-xl pl-3.5 pr-10 py-3 text-sm bg-surface text-ink border border-line cursor-pointer
                        transition-colors hover:border-navy/40
                        focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                        ' . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : ''),
        ]) }}>
            {{ $slot }}
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted transition-colors group-focus-within:text-navy"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @endif
</div>
