@props(['href', 'active' => false, 'badge' => null])

@php
$path = ltrim(parse_url($href, PHP_URL_PATH), '/');
$isActive = $active || request()->is($path) || request()->is($path . '/*');
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors
          {{ $isActive
              ? 'bg-orange-500/20 text-orange-400 font-medium border-l-2 border-orange-400'
              : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
    {{ $slot }}
    @if ($badge)
        <span class="ml-auto bg-red-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center">
            {{ $badge }}
        </span>
    @endif
</a>
