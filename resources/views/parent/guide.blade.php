<x-parent-portal title="User Guide">
    <div class="max-w-3xl space-y-6">

        <div class="mb-6">
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.guide.title') }}</h2>
            <p class="text-sm text-muted">{{ __('messages.portal.guide.subtitle') }}</p>
        </div>

        <div x-data="{ open: 'players' }" class="space-y-3">

            @foreach ([
                ['key' => 'players', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['key' => 'qr', 'icon' => 'M3 3h6v6H3V3zm0 12h6v6H3v-6zM15 3h6v6h-6V3zm0 4h2m4 4h-6v6h6v-6zm-4 2h2'],
                ['key' => 'sessions', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['key' => 'leave', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['key' => 'makeup', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ['key' => 'private', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
                ['key' => 'payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m0-2c-1.11 0-2.08-.402-2.599-1'],
                ['key' => 'reports', 'icon' => 'M9 17v-6h2v6H9zm4 0V7h2v10h-2zm-8 0v-3h2v3H5z'],
                ['key' => 'calendar', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ] as $topic)
                <x-card padding="p-0">
                    <button type="button" @click="open = (open === '{{ $topic['key'] }}') ? null : '{{ $topic['key'] }}'"
                            class="w-full flex items-center gap-3 px-6 py-4 text-left">
                        <span class="w-9 h-9 rounded-lg bg-navy/8 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $topic['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="flex-1 font-semibold text-ink">{{ __('messages.portal.guide.' . $topic['key'] . '_title') }}</span>
                        <svg class="w-4 h-4 text-muted shrink-0 transition-transform" :class="open === '{{ $topic['key'] }}' ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === '{{ $topic['key'] }}'" x-collapse x-cloak>
                        <div class="px-6 pb-5 pl-[4.25rem] text-sm text-muted leading-relaxed">
                            {{ __('messages.portal.guide.' . $topic['key'] . '_body') }}
                        </div>
                    </div>
                </x-card>
            @endforeach

        </div>

        <x-card>
            <p class="text-sm text-muted">
                {{ __('messages.portal.guide.contact_prompt') }}
            </p>
        </x-card>
    </div>
</x-parent-portal>
