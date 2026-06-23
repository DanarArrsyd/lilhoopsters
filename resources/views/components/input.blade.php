@props(['label' => null, 'error' => null, 'helper' => null])

@php
    $fieldId  = $attributes->get('id') ?? $attributes->get('name');
    $isPassword = $attributes->get('type') === 'password';
    $baseClass  = 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink
                   border border-line placeholder:text-faint
                   focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                   disabled:bg-off disabled:text-faint '
                 . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : '');
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

    @if ($isPassword)
        <div class="relative" x-data="{ show: false }">
            <input {{ $attributes->merge([
                'id'    => $fieldId,
                'class' => $baseClass . ' pr-10',
            ]) }} :type="show ? 'text' : 'password'">

            <button
                type="button"
                @click="show = !show"
                tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-faint hover:text-ink transition-colors"
                :aria-label="show ? 'Hide password' : 'Show password'"
            >
                {{-- Eye open --}}
                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                {{-- Eye off --}}
                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                </svg>
            </button>
        </div>
    @else
        <input {{ $attributes->merge([
            'id'    => $fieldId,
            'class' => $baseClass,
        ]) }}>
    @endif

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @elseif ($helper)
        <p class="text-xs text-muted">{{ $helper }}</p>
    @endif
</div>
