<x-card class="mb-4">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.quick_actions') }}</p>
    </div>

    <div x-data="{ activeModal: null }"
         @open-modal.window="activeModal = $event.detail"
         @keydown.escape.window="activeModal = null">

        <div class="grid grid-cols-3 gap-2">
            <button @click="$dispatch('open-modal', 'leave-request')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 hover:bg-off transition-colors">
                <span class="w-8 h-8 rounded-lg bg-navy/8 text-navy flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-ink">{{ __('messages.portal.home.leave_request') }}</span>
            </button>
            <button @click="$dispatch('open-modal', 'makeup-class')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 hover:bg-off transition-colors">
                <span class="w-8 h-8 rounded-lg bg-navy/8 text-navy flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-ink">{{ __('messages.portal.home.makeup_class') }}</span>
            </button>
            <button @click="$dispatch('open-modal', 'private-session')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 hover:bg-off transition-colors">
                <span class="w-8 h-8 rounded-lg bg-navy/8 text-navy flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-ink">{{ __('messages.portal.home.private_session') }}</span>
            </button>
        </div>

        <div x-show="activeModal === 'leave-request'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.leave-requests')
            </div>
        </div>

        <div x-show="activeModal === 'makeup-class'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.make-up-classes')
            </div>
        </div>

        <div x-show="activeModal === 'private-session'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.private-sessions')
            </div>
        </div>
    </div>
</x-card>
