@props(['showQr' => false, 'qrSvg' => '', 'activeChild' => null])

<x-card class="mb-4" tone="flat">
    <div class="flex items-center gap-2 mb-4">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.quick_actions') }}</p>
    </div>

    {{-- Four, ranked by how often a parent reaches for them. The other four
         used to sit here as equal tiles; they are in the nav now — the More
         sheet on mobile, the More dropdown on desktop — so an eight-tile grid
         was giving the rare ones the same weight as the daily ones. --}}
    <div class="grid grid-cols-4 gap-2">
        {{-- 1. Presence QR — used every session --}}
        <button wire:click="openQr" wire:loading.attr="disabled" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
            </span>
            <span class="text-2xs font-semibold text-ink leading-tight">{{ __('messages.portal.home.qr_code') }}</span>
        </button>
        {{-- 4. Request Leave --}}
        <a href="{{ route('parent.leaves') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <span class="text-2xs font-semibold text-ink leading-tight">{{ __('messages.portal.home.leave_request') }}</span>
        </a>
        {{-- 5. Makeup Class --}}
        <a href="{{ route('parent.payments') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </span>
            <span class="text-2xs font-semibold text-ink leading-tight">{{ __('messages.portal.home.payments') }}</span>
        </a>
        {{-- 7. Attendance --}}
        <a href="{{ route('parent.report-cards') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </span>
            <span class="text-2xs font-semibold text-ink leading-tight">{{ __('messages.portal.home.report_cards') }}</span>
        </a>
    </div>
</x-card>

@if ($showQr)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeQr"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-xs text-center">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="font-extrabold uppercase tracking-tight text-navy text-sm">{{ __('messages.players.qr_code') }} — {{ $activeChild?->name }}</h3>
                <button wire:click="closeQr" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6">
                <div class="inline-block p-3 bg-surface border-2 border-line rounded-xl">
                    {!! $qrSvg !!}
                </div>
                <p class="text-xs text-faint mt-3">{{ __('messages.players.qr_hint') }}</p>
            </div>
        </div>
    </div>
@endif
