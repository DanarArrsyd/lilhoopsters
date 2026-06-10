@props(['label' => null, 'error' => null, 'helper' => null])

@php
    $fieldId = $attributes->get('id') ?? $attributes->get('name');
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label @if($fieldId) for="{{ $fieldId }}" @endif class="block text-xs font-semibold uppercase tracking-wide text-navy">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-[#DC2626] ml-0.5">*</span>
            @endif
        </label>
    @endif

    <input {{ $attributes->merge([
        'id'    => $fieldId,
        'class' => 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink
                    border border-line placeholder:text-faint
                    focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                    disabled:bg-off disabled:text-faint
                    ' . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : ''),
    ]) }}>

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @elseif ($helper)
        <p class="text-xs text-muted">{{ $helper }}</p>
    @endif
</div>
