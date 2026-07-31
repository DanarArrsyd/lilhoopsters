<div class="max-w-6xl mx-auto space-y-6"
     x-data="qrScanner($wire)"
     x-init="captureGps()"
     @show-checkin-warning.window="showCheckinWarning = true">

    <x-admin.page-header :title="__('messages.coach.qr_scanner.title')" :subtitle="__('messages.coach.qr_scanner.subtitle')" />

    {{-- Setup card --}}
    <x-card padding="p-5">
        <div class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                {{-- Date first — drives day-of-week filter on schedule dropdown --}}
                <x-input type="date" wire:model.live="scanDate" label="Date" max="{{ today()->toDateString() }}" :error="$errors->first('scanDate')" />

                <x-select wire:model.live="scheduleId" label="Schedule" :error="$errors->first('scheduleId')">
                    <option value="">
                        {{ $scanDate ? __('messages.coach.qr_scanner.select_schedule') : __('messages.coach.qr_scanner.pick_date') }}
                    </option>
                    @foreach ($schedules as $schedule)
                        <option value="{{ $schedule->id }}">
                            {{ $schedule->program->name }} · {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            ({{ $schedule->location->name }})
                        </option>
                    @endforeach
                </x-select>
            </div>

            @if (!$scannerActive)
                <x-btn variant="success" wire:click="activateScanner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    {{ __('messages.coach.qr_scanner.activate') }}
                </x-btn>
            @else
                <x-btn variant="secondary" wire:click="deactivateScanner" x-on:click="stopScanner()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('messages.coach.qr_scanner.stop') }}
                </x-btn>
            @endif
        </div>
    </x-card>

    {{-- Roster panel — visible as soon as schedule + date are selected --}}
    @if ($scheduleId && $scanDate)
        <div class="grid {{ $scannerActive ? 'sm:grid-cols-2' : 'grid-cols-1' }} gap-6">

            {{-- Camera viewfinder (only when scanner active) --}}
            @if ($scannerActive)
            <x-card padding="p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-navy mb-3">{{ __('messages.coach.qr_scanner.camera') }}</p>

                {{-- wire:ignore keeps Livewire from touching the camera DOM on re-render --}}
                <div id="qr-reader" wire:ignore class="w-full rounded-xl overflow-hidden border border-line"></div>

                <p class="text-xs text-faint mt-2 text-center">{{ __('messages.coach.qr_scanner.point_camera') }}</p>

                {{-- Scan result notification — outside wire:ignore so it updates normally --}}
                @if ($lastScanMessage)
                    <div @class([
                        'mt-3 px-4 py-3 rounded-xl text-sm font-semibold border flex items-center gap-2',
                        'bg-[#F0FDF4] text-[#15803D] border-[#BBF7D0]' => $lastScanStatus === 'success',
                        'bg-[#FEF9C3] text-[#854D0E] border-[#FDE68A]' => $lastScanStatus === 'duplicate',
                        'bg-[#FEF2F2] text-[#B91C1C] border-[#FECACA]' => !in_array($lastScanStatus, ['success', 'duplicate']),
                    ])>
                        @if ($lastScanStatus === 'success')
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @elseif ($lastScanStatus === 'duplicate')
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                        {{ $lastScanMessage }}
                    </div>
                @endif
            </x-card>
            @endif

            {{-- Attendance roster --}}
            <x-card padding="p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-navy">
                        {{ __('messages.coach.qr_scanner.roster') }}
                    </p>
                    <span class="text-xs font-semibold text-[#15803D] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-0.5 rounded-full">
                        {{ __('messages.coach.qr_scanner.present_count', ['a' => $presentCount, 'b' => $roster->count()]) }}
                    </span>
                </div>

                @if ($roster->isEmpty())
                    <p class="text-sm text-faint">{{ __('messages.coach.qr_scanner.no_students') }}</p>
                @else
                    <div class="space-y-1.5 max-h-[480px] overflow-y-auto pr-1">
                        @foreach ($roster as $row)
                            @php
                                $status = $row['status'];
                                $child  = $row['child'];
                                $init   = strtoupper(substr($child->name, 0, 1));
                            @endphp

                            @if ($status === 'present')
                                {{-- ✅ Present --}}
                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#F0FDF4] border border-[#BBF7D0]">
                                    <div class="w-7 h-7 rounded-full bg-[#15803D] flex items-center justify-center text-2xs font-bold text-white shrink-0">{{ $init }}</div>
                                    <span class="text-sm font-semibold text-[#15803D] flex-1 truncate">{{ $child->name }}</span>
                                    <span class="text-3xs font-bold text-[#15803D] flex items-center gap-1 shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('messages.coach.qr_scanner.present') }}
                                    </span>
                                    <button type="button"
                                            wire:click="undoPresent({{ $child->id }})"
                                            wire:confirm="Undo attendance for {{ $child->name }}?"
                                            title="Undo"
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-white border border-[#FECACA] text-[#B91C1C] hover:bg-[#FEF2F2] transition-colors shrink-0 text-xs font-bold">×</button>
                                </div>

                            @elseif ($status === 'no_show')
                                {{-- ❌ No Show (explicitly recorded) --}}
                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[#FEF2F2] border border-[#FECACA]">
                                    <div class="w-7 h-7 rounded-full bg-[#B91C1C]/20 flex items-center justify-center text-2xs font-bold text-[#B91C1C] shrink-0">{{ $init }}</div>
                                    <span class="text-sm font-semibold text-[#B91C1C] flex-1 truncate">{{ $child->name }}</span>
                                    <span class="text-3xs font-bold text-[#B91C1C] shrink-0">No Show</span>
                                    <button type="button"
                                            wire:click="markPresent({{ $child->id }})"
                                            title="Mark present"
                                            class="text-3xs font-bold text-[#15803D] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-1 rounded-lg hover:bg-[#DCFCE7] transition-colors shrink-0">
                                        Present
                                    </button>
                                    <button type="button"
                                            wire:click="undoPresent({{ $child->id }})"
                                            wire:confirm="Remove no-show record for {{ $child->name }}?"
                                            title="Clear record"
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-white border border-[#FECACA] text-[#B91C1C] hover:bg-[#FEF2F2] transition-colors shrink-0 text-xs font-bold">×</button>
                                </div>

                            @elseif (in_array($status, ['sick', 'permit']))
                                {{-- 🟡 Sick / Permit (leave request) --}}
                                @php
                                    $leaveLabel = $status === 'sick' ? __('messages.admin.attendances.opt_sick') : __('messages.admin.attendances.opt_permit');
                                @endphp
                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100">
                                    <div class="w-7 h-7 rounded-full bg-amber-200 flex items-center justify-center text-2xs font-bold text-amber-800 shrink-0">{{ $init }}</div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-semibold text-ink truncate block">{{ $child->name }}</span>
                                        <span class="text-3xs text-amber-700">Leave request submitted</span>
                                    </div>
                                    <span class="text-3xs font-bold text-amber-700 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded-full shrink-0">
                                        {{ $leaveLabel }}
                                    </span>
                                    <button type="button"
                                            wire:click="markPresent({{ $child->id }})"
                                            title="Override — mark present"
                                            class="text-3xs font-bold text-[#15803D] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-1 rounded-lg hover:bg-[#DCFCE7] transition-colors shrink-0">
                                        Present
                                    </button>
                                </div>

                            @else
                                {{-- ⬜ No Record --}}
                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-off border border-line">
                                    <div class="w-7 h-7 rounded-full bg-navy/10 flex items-center justify-center text-2xs font-bold text-navy shrink-0">{{ $init }}</div>
                                    <span class="text-sm text-ink flex-1 truncate">{{ $child->name }}</span>
                                    <span class="text-3xs font-semibold text-faint bg-line/60 px-2 py-0.5 rounded-full shrink-0">No Record</span>
                                    <button type="button"
                                            wire:click="markPresent({{ $child->id }})"
                                            title="Mark present"
                                            class="text-3xs font-bold text-[#15803D] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-1 rounded-lg hover:bg-[#DCFCE7] transition-colors shrink-0">
                                        Present
                                    </button>
                                    <button type="button"
                                            wire:click="markNoShow({{ $child->id }})"
                                            title="Mark no show"
                                            class="text-3xs font-bold text-[#B91C1C] bg-[#FEF2F2] border border-[#FECACA] px-2 py-1 rounded-lg hover:bg-[#FEE2E2] transition-colors shrink-0">
                                        No Show
                                    </button>
                                </div>
                            @endif

                        @endforeach
                    </div>
                @endif
            </x-card>

        </div>
    @endif

    {{-- Check-in required modal — Alpine-driven, no Livewire re-render needed --}}
<div x-show="showCheckinWarning"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-navy/50 backdrop-blur-sm"
         @click="showCheckinWarning = false"></div>

    <div class="relative bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0">

        <div class="flex flex-col items-center text-center px-6 pt-7 pb-5">
            <div class="w-14 h-14 bg-amber-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-navy mb-2">{{ __('messages.coach.qr_scanner.checkin_required_title') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">
                {!! __('messages.coach.qr_scanner.checkin_required_body') !!}
            </p>
        </div>

        <div class="px-6 pb-6 grid grid-cols-2 gap-2.5">
            <button type="button" @click="showCheckinWarning = false"
                    class="py-3 rounded-xl border border-gray-200 bg-white text-navy text-sm font-bold hover:bg-gray-50 transition-colors">
                {{ __('messages.common.cancel') }}
            </button>
            <a href="{{ route('coach.checkin') }}"
               class="py-3 rounded-xl bg-navy text-off text-sm font-bold text-center hover:bg-navy/90 transition-colors">
                {{ __('messages.coach.qr_scanner.go_to_checkin') }}
            </a>
        </div>
    </div>

    {{-- html5-qrcode CDN --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
function qrScanner(wire) {
    return {
        html5QrCode: null,
        showCheckinWarning: false,

        captureGps() {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(
                pos => {
                    wire.set('latitude',  pos.coords.latitude);
                    wire.set('longitude', pos.coords.longitude);
                },
                () => {},
                { timeout: 8000, maximumAge: 120000 }
            );
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

</div>
