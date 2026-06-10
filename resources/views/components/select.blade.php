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

    <select {{ $attributes->merge([
        'id'    => $fieldId,
        'class' => 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink border border-line
                    focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                    ' . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : ''),
    ]) }}>
        {{ $slot }}
    </select>

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @endif
</div>
