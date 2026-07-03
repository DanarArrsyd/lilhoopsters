<div class="max-w-3xl mx-auto" x-data="paymentVerifier($wire)">

    <x-admin.page-header
        :title="__('messages.admin.verify_payment.title')"
        :subtitle="__('messages.admin.verify_payment.subtitle')" />

    <x-card>
        {{-- Scanner toggle --}}
        <div class="flex items-center justify-between gap-4 mb-5">
            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.admin.verify_payment.scanner') }}</p>
            @if (!$scannerActive)
                <x-btn variant="success" wire:click="activateScanner" x-on:click="captureStart()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    {{ __('messages.admin.verify_payment.activate') }}
                </x-btn>
            @else
                <x-btn variant="secondary" wire:click="deactivateScanner" x-on:click="stopScanner()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('messages.admin.verify_payment.stop') }}
                </x-btn>
            @endif
        </div>

        {{-- Camera viewfinder --}}
        @if ($scannerActive)
            <div id="qr-reader" wire:ignore class="w-full rounded-xl overflow-hidden border border-line mb-2"></div>
            <p class="text-xs text-faint mb-5 text-center">{{ __('messages.admin.verify_payment.point_camera') }}</p>
        @endif

        {{-- Manual fallback --}}
        <div class="border-t border-line pt-5">
            <p class="text-[11px] font-bold uppercase tracking-wide text-faint mb-2">{{ __('messages.admin.verify_payment.manual') }}</p>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-input wire:model="manualCode" placeholder="TRX-XXXXXXXX" :error="$errors->first('manualCode')" />
                </div>
                <x-btn variant="primary" wire:click="verifyManual" wire:loading.attr="disabled">
                    {{ __('messages.admin.verify_payment.verify') }}
                </x-btn>
            </div>
        </div>
    </x-card>

    {{-- ── Result ── --}}
    @if ($resultStatus)
        <div class="mt-5">
            @if ($resultStatus === 'valid')
                <div class="rounded-2xl border-2 border-[#15803D]/30 bg-[#F0FDF4] overflow-hidden">
                    <div class="bg-[#15803D] text-white px-5 py-3 flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-extrabold uppercase tracking-wide leading-none">{{ __('messages.admin.verify_payment.valid') }}</p>
                            <p class="text-[11px] text-white/70 mt-0.5">{{ $result['code'] }}</p>
                        </div>
                    </div>
                    <div class="p-5 space-y-2.5">
                        <div class="flex justify-between gap-4">
                            <span class="text-xs text-muted">{{ __('messages.receipt.total_paid') }}</span>
                            <span class="text-lg font-extrabold text-navy">Rp {{ number_format((int) $result['amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-y-2.5 gap-x-4 pt-2 border-t border-[#15803D]/15">
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-faint">{{ __('messages.receipt.billed_to') }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $result['parent'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-faint">{{ __('messages.receipt.player') }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $result['child'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-faint">{{ __('messages.receipt.description') }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $result['package'] ?? '—' }}</p>
                                @if ($result['location'])<p class="text-[11px] text-faint">{{ $result['location'] }}</p>@endif
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-faint">{{ __('messages.receipt.paid_at') }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $result['paid_at'] ?? '—' }}</p>
                            </div>
                            @if ($result['verified_by'])
                            <div class="col-span-2">
                                <p class="text-[10px] uppercase tracking-wide text-faint">{{ __('messages.receipt.verified_by') }}</p>
                                <p class="text-sm font-semibold text-ink">{{ $result['verified_by'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($resultStatus === 'unpaid')
                <div class="rounded-2xl border-2 border-[#D97706]/30 bg-[#FFFBEB] px-5 py-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-[#D97706] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-wide text-[#92400E]">{{ __('messages.admin.verify_payment.unpaid') }}</p>
                        <p class="text-xs text-[#92400E]/80 mt-1">
                            {{ $result['code'] }} — {{ __('messages.admin.verify_payment.unpaid_desc', ['status' => __('messages.status.'.$result['status'])]) }}
                        </p>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border-2 border-[#DC2626]/30 bg-[#FEF2F2] px-5 py-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-[#DC2626] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-wide text-[#B91C1C]">{{ __('messages.admin.verify_payment.not_found') }}</p>
                        <p class="text-xs text-[#B91C1C]/80 mt-1">{{ $result['code'] }} — {{ __('messages.admin.verify_payment.not_found_desc') }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- html5-qrcode CDN --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
    function paymentVerifier(wire) {
        return {
            html5QrCode: null,

            captureStart() {
                // scanner starts via the $wire.scannerActive watcher below
            },

            stopScanner() {
                if (this.html5QrCode) {
                    this.html5QrCode.stop().catch(() => {});
                    this.html5QrCode = null;
                }
            },

            startScanner() {
                this.$nextTick(() => {
                    if (!document.getElementById('qr-reader')) return;

                    this.html5QrCode = new Html5Qrcode('qr-reader');
                    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

                    this.html5QrCode.start(
                        { facingMode: 'environment' },
                        config,
                        (decodedText) => {
                            wire.processQr(decodedText);
                            this.html5QrCode.pause(true);
                            setTimeout(() => {
                                if (this.html5QrCode) this.html5QrCode.resume();
                            }, 2500);
                        }
                    ).catch(err => console.warn('QR scanner error:', err));
                });
            },

            init() {
                this.$watch('$wire.scannerActive', (active) => {
                    active ? this.startScanner() : this.stopScanner();
                });

                @if ($scannerActive)
                this.startScanner();
                @endif
            }
        };
    }
    </script>

</div>
