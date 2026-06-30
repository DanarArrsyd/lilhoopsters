<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.home.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.portal.home.subtitle') }}</p>
    </div>

    @if ($children->isEmpty())
        <x-empty-state
            :title="__('messages.portal.home.empty_title')"
            :description="__('messages.portal.home.empty_desc')">
            <x-slot name="action">
                <x-btn href="{{ route('parent.enroll') }}" variant="add">{{ __('messages.portal.home.add_player') }}</x-btn>
            </x-slot>
        </x-empty-state>
    @else
        @if ($sectionFailed ?? false)
            <div class="mb-4 px-4 py-3 rounded-xl bg-[#B91C1C]/10 text-[#B91C1C] text-sm flex items-center justify-between">
                <span>{{ __('messages.portal.home.section_error') }}</span>
                <button wire:click="$refresh" class="font-semibold underline shrink-0 ml-3">{{ __('messages.portal.home.retry') }}</button>
            </div>
        @endif
        {{-- Sections added in Tasks 3-6 --}}
        <div id="portal-home-sections"></div>
    @endif
</div>
