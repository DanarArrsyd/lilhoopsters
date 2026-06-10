<div class="space-y-6" x-data="qrScanner($wire)">

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">QR Scanner</h2>
        <p class="text-sm text-muted">Scan student QR codes to record attendance.</p>
    </div>

    {{-- Setup card --}}
    <x-card padding="p-5">
        <div class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <x-select wire:model="scheduleId" label="Schedule" :error="$errors->first('scheduleId')">
                    <option value="">Select schedule...</option>
                    @foreach ($schedules as $schedule)
                        <option value="{{ $schedule->id }}">
                            {{ $schedule->program->name }} — {{ ucfirst($schedule->day_of_week) }}
                            {{ $schedule->start_time }} ({{ $schedule->location->name }})
                        </option>
                    @endforeach
                </x-select>
                <x-input type="date" wire:model="scanDate" label="Date" :error="$errors->first('scanDate')" />
            </div>

            @if (!$scannerActive)
                <x-btn wire:click="activateScanner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Activate Scanner
                </x-btn>
            @else
                <x-btn variant="secondary" wire:click="deactivateScanner" x-on:click="stopScanner()">
                    Stop Scanner
                </x-btn>
            @endif
        </div>
    </x-card>

    {{-- Scanner area --}}
    @if ($scannerActive)
        <div class="grid sm:grid-cols-2 gap-6">

            {{-- Camera viewfinder --}}
            <x-card padding="p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-navy mb-3">Camera</p>

                {{-- Scan result feedback --}}
                @if ($lastScanMessage)
                    <div class="mb-3 px-4 py-3 rounded-xl text-sm font-medium border
                        {{ $lastScanStatus === 'success'
                            ? 'bg-[#15803D]/8 text-[#15803D] border-[#15803D]/20'
                            : 'bg-[#B91C1C]/8 text-[#B91C1C] border-[#B91C1C]/20' }}">
                        {{ $lastScanMessage }}
                    </div>
                @endif

                <div id="qr-reader" class="w-full rounded-xl overflow-hidden border border-line"></div>
                <p class="text-xs text-faint mt-2 text-center">Point the camera at a student's QR code</p>
            </x-card>

            {{-- Today's scanned attendance --}}
            <x-card padding="p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-navy mb-3">
                    Present Today ({{ count($todayAttendances) }})
                </p>
                @if (empty($todayAttendances) || count($todayAttendances) === 0)
                    <p class="text-sm text-faint">No scans yet.</p>
                @else
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        @foreach ($todayAttendances as $att)
                            <div class="flex items-center gap-3 py-2 border-b border-line last:border-0">
                                <div class="w-8 h-8 rounded-full bg-navy/8 text-navy flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($att->child->name, 0, 1)) }}
                                </div>
                                <span class="text-sm text-ink">{{ $att->child->name }}</span>
                                <svg class="w-4 h-4 text-[#15803D] ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

        </div>
    @endif

</div>

{{-- html5-qrcode CDN --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
function qrScanner(wire) {
    return {
        html5QrCode: null,

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
                        // brief cooldown to avoid double-scans
                        this.html5QrCode.pause(true);
                        setTimeout(() => {
                            if (this.html5QrCode) this.html5QrCode.resume();
                        }, 2500);
                    }
                ).catch(err => console.warn('QR scanner error:', err));
            });
        },

        init() {
            // Watch for Livewire re-renders when scanner is active
            this.$watch('$wire.scannerActive', (active) => {
                if (active) {
                    this.startScanner();
                } else {
                    this.stopScanner();
                }
            });

            @if ($scannerActive)
            this.startScanner();
            @endif
        }
    };
}
</script>
