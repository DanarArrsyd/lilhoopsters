@props(['label'])

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button @click="open = !open" type="button"
            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors
                   {{ $attributes->get('data-has-active') === 'true' ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ $label }}
        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute left-0 mt-2 w-56 bg-surface border border-line rounded-xl shadow-lg py-1.5 z-40">
        {{ $slot }}
    </div>
</div>
