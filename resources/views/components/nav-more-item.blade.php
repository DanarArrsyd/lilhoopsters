{{-- One tile inside <x-nav-more-sheet>. Icon goes in the slot. --}}
@props([
    'href',
    'label',
    'active' => false,
])

<a href="{{ $href }}"
   @class([
       'flex flex-col items-center justify-center gap-2 rounded-xl px-2 py-4 text-center transition-colors',
       'bg-navy text-off'                                  => $active,
       'bg-off text-muted hover:bg-line/50 hover:text-navy' => ! $active,
   ])>
    <span class="shrink-0">{{ $slot }}</span>
    <span class="text-[11px] font-semibold leading-tight">{{ $label }}</span>
</a>
