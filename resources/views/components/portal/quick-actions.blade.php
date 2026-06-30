<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-3">{{ __('messages.portal.home.quick_actions') }}</p>

    <div x-data="{ activeModal: null }"
         @open-modal.window="activeModal = $event.detail"
         @keydown.escape.window="activeModal = null">

        <div class="grid grid-cols-3 gap-2">
            <button @click="$dispatch('open-modal', 'leave-request')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
                <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.leave_request') }}</span>
            </button>
            <button @click="$dispatch('open-modal', 'makeup-class')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
                <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.makeup_class') }}</span>
            </button>
            <button @click="$dispatch('open-modal', 'private-session')"
                    class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
                <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.private_session') }}</span>
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
