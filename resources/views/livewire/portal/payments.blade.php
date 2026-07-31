<div>
    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if ($uploadingId)
    {{-- ==================== UPLOAD SCREEN ==================== --}}

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-6">
            <button type="button" wire:click="cancelUpload"
                    class="w-9 h-9 flex items-center justify-center rounded-xl border border-line bg-surface text-navy hover:bg-off transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <div>
                <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy leading-tight">{{ __('messages.payments.upload_proof') }}</h2>
                @if ($uploadingTransaction)
                    <p class="text-xs text-faint">{{ $uploadingTransaction->transaction_code }}</p>
                @endif
            </div>
        </div>

        {{-- Transaction summary --}}
        @if ($uploadingTransaction)
        <div class="bg-surface border border-line rounded-2xl p-4 mb-4">
            <p class="text-3xs font-bold uppercase tracking-wider text-faint mb-3">{{ __('messages.payments.txn_details') }}</p>
            <div class="flex items-center gap-3 pb-3 border-b border-line mb-3">
                <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center text-off font-extrabold text-sm flex-shrink-0">
                    {{ strtoupper(substr($uploadingTransaction->child?->name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-navy truncate">{{ $uploadingTransaction->child?->name ?? '—' }}</p>
                    <p class="text-xs text-faint truncate">
                        {{ $uploadingTransaction->package?->name ?? '—' }}@if ($uploadingTransaction->package?->location) · {{ $uploadingTransaction->package->location->name }}@endif
                    </p>
                </div>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-muted">{{ __('messages.payments.total_payment') }}</span>
                <span class="text-lg font-extrabold text-navy">Rp {{ number_format($uploadingTransaction->amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-muted">{{ __('messages.payments.status_label') }}</span>
                <x-badge :status="$uploadingTransaction->status">{{ __('messages.status.'.$uploadingTransaction->status) }}</x-badge>
            </div>
        </div>
        @endif

        {{-- Upload form --}}
        <div class="bg-surface border border-line rounded-2xl overflow-hidden mb-4">

            {{-- Proof file --}}
            <div class="p-4 border-b border-line">
                <p class="text-3xs font-bold uppercase tracking-wider text-navy mb-3">
                    {{ __('messages.payments.proof_label') }} <span class="text-[#B91C1C]">*</span>
                </p>
                <label class="block cursor-pointer">
                    <input type="file" wire:model="proofFile" accept="image/*" class="sr-only">
                    <div class="border-2 border-dashed rounded-xl p-6 text-center transition-colors
                                {{ $proofFile && !$errors->has('proofFile') ? 'border-[#BBF7D0] bg-[#F0FDF4]' : 'border-line hover:border-navy/30' }}">
                        @if ($proofFile && !$errors->has('proofFile'))
                            <img src="{{ $proofFile->temporaryUrl() }}" alt="Preview"
                                 class="h-24 w-auto mx-auto rounded-lg object-cover mb-2">
                            <p class="text-xs font-semibold text-[#15803D]">{{ __('messages.payments.photo_selected') }}</p>
                        @else
                            <div class="w-10 h-10 bg-off rounded-full flex items-center justify-center mx-auto mb-2 border border-line">
                                <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-navy mb-0.5">{{ __('messages.payments.tap_upload') }}</p>
                            <p class="text-xs text-faint">{{ __('messages.payments.file_hint') }}</p>
                        @endif
                    </div>
                </label>
                <div wire:loading wire:target="proofFile" class="text-xs text-faint mt-2 text-center">{{ __('messages.payments.uploading') }}</div>
                @error('proofFile') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Admin payment accounts --}}
            <div class="p-4">
                <p class="text-3xs font-bold uppercase tracking-wider text-navy mb-3">
                    {{ __('messages.payments.transfer_to') }}
                </p>
                @if ($paymentAccounts->isEmpty())
                    <p class="text-xs text-faint text-center py-3">
                        {{ __('messages.payments.no_accounts') }}
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach ($paymentAccounts as $acc)
                            <div class="flex items-center justify-between gap-3 bg-off border border-line rounded-xl px-4 py-3"
                                 x-data="{ copied: false, copyText: '{{ __('messages.payments.copy') }}', copiedText: '{{ __('messages.payments.copied') }}' }">
                                <div class="min-w-0">
                                    <p class="text-3xs font-bold uppercase tracking-wider text-faint mb-0.5">
                                        {{ $acc->bank_name }}
                                    </p>
                                    <p class="text-base font-extrabold text-navy tracking-wide leading-tight">
                                        {{ $acc->account_number }}
                                    </p>
                                    <p class="text-xs text-muted mt-0.5">{{ $acc->account_holder }}</p>
                                </div>
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $acc->account_number }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg border transition-all duration-150
                                               text-xs font-bold"
                                        :class="copied
                                            ? 'bg-[#DCFCE7] border-[#BBF7D0] text-[#15803D]'
                                            : 'bg-surface border-line text-navy hover:bg-navy hover:text-off hover:border-navy'">
                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg x-show="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="copied ? copiedText : copyText"></span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- T&C checkbox + modal --}}
        <div class="bg-surface border border-line rounded-2xl p-4 mb-4" x-data="{ showTnc: false }">
            <label class="flex items-start gap-3 cursor-pointer select-none">
                <input type="checkbox" wire:model="agreedToTnc" id="tnc-check"
                       class="mt-0.5 flex-shrink-0 w-5 h-5 rounded border-2 border-line cursor-pointer accent-navy">
                <p class="text-sm text-ink leading-snug">
                    {{ __('messages.payments.tnc_agree_pre') }}
                    <button type="button" @click.prevent="showTnc = true"
                            class="font-semibold text-navy underline underline-offset-2 hover:opacity-70 transition-opacity cursor-pointer">
                        {{ __('messages.payments.tnc_title') }}
                    </button>.
                </p>
            </label>
            @error('agreedToTnc') <p class="text-xs text-[#B91C1C] mt-2 ml-8">{{ $message }}</p> @enderror

            {{-- T&C Modal --}}
            <div x-show="showTnc" x-cloak
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-navy/50 backdrop-blur-sm" @click="showTnc = false"></div>

                {{-- Sheet --}}
                <div class="relative bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md shadow-2xl max-h-[80vh] flex flex-col overflow-hidden"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    {{-- Handle bar (mobile) --}}
                    <div class="flex justify-center pt-3 pb-1 sm:hidden flex-shrink-0">
                        <div class="w-10 h-1 rounded-full bg-line"></div>
                    </div>

                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line flex-shrink-0">
                        <h3 class="text-base font-extrabold uppercase tracking-tight text-navy">
                            {{ __('messages.payments.tnc_title') }}
                        </h3>
                        <button type="button" @click="showTnc = false"
                                class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-off transition-colors text-muted">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Scrollable body --}}
                    <div class="overflow-y-auto px-5 py-5 space-y-4 text-sm text-ink leading-relaxed">
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_1_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_1_body') !!}</p>
                        </div>
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_2_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_2_body') !!}</p>
                        </div>
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_3_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_3_body') !!}</p>
                        </div>
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_4_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_4_body') !!}</p>
                        </div>
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_5_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_5_body') !!}</p>
                        </div>
                        <div>
                            <p class="font-bold text-navy mb-1">{{ __('messages.payments.tnc_6_title') }}</p>
                            <p class="text-muted">{!! __('messages.payments.tnc_6_body') !!}</p>
                        </div>
                    </div>

                    {{-- Footer button --}}
                    <div class="px-5 py-4 border-t border-line flex-shrink-0">
                        <button type="button" @click="showTnc = false"
                                class="w-full bg-navy text-off font-bold text-sm py-3 rounded-xl hover:opacity-90 transition-opacity">
                            {{ __('messages.payments.i_understand') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col gap-2.5">
            <button type="button" wire:click="uploadProof" wire:loading.attr="disabled" wire:target="uploadProof"
                    class="w-full bg-navy text-off font-bold text-sm py-3.5 rounded-xl
                           hover:bg-navy-2 transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="uploadProof">{{ __('messages.payments.submit_proof') }}</span>
                <span wire:loading wire:target="uploadProof">{{ __('messages.payments.sending') }}</span>
            </button>
            <button type="button" wire:click="cancelUpload"
                    class="w-full bg-surface text-navy font-semibold text-sm py-3.5 rounded-xl
                           border border-line hover:bg-off transition-colors">
                {{ __('messages.common.cancel') }}
            </button>
            <p class="text-center text-xs text-faint">{{ __('messages.payments.verify_notice') }}</p>
        </div>

    @else
    {{-- ==================== LIST SCREEN ==================== --}}

        {{-- Page header --}}
        <div class="mb-6">
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.payments.title') }}</h2>
            <p class="text-sm text-muted">{{ __('messages.pages.payments.subtitle') }}</p>
        </div>

        {{-- Pending summary card --}}
        @if ($pendingCount > 0)
        <div class="bg-navy rounded-2xl p-4 mb-4">
            <p class="text-3xs font-bold uppercase tracking-wider text-off/50 mb-1">{{ __('messages.payments.pending_label') }}</p>
            <p class="text-3xl font-extrabold text-off mb-2 tracking-tight">Rp {{ number_format($pendingTotal, 0, ',', '.') }}</p>
            <div class="flex items-center gap-2">
                <span class="bg-[#B45309] text-[#FEF3C7] text-3xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wide">
                    {{ __('messages.payments.pending_count', ['n' => $pendingCount]) }}
                </span>
                <span class="text-xs text-off/40">{{ __('messages.payments.upload_to_confirm') }}</span>
            </div>
        </div>
        @endif

        {{-- Status filter pills --}}
        <div class="flex gap-2 mb-4 overflow-x-auto pb-1" style="scrollbar-width:none;">
            @foreach (['' => __('messages.payments.filter_all'), 'pending' => __('messages.status.pending'), 'paid' => __('messages.status.paid'), 'rejected' => __('messages.status.rejected'), 'expired' => __('messages.status.expired')] as $val => $label)
                <button type="button" wire:click="$set('filterStatus', '{{ $val }}')"
                        class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-full border transition-all whitespace-nowrap
                               {{ $filterStatus === $val
                                  ? 'bg-navy text-off border-transparent'
                                  : 'bg-surface text-muted border-line hover:border-navy/30 hover:text-navy' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Transaction card list.
             Two columns from lg: a transaction carries about four facts, and one
             row per screen-width put the child's name and its status badge 1400px
             apart with nothing in between. --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-start">
            @forelse ($transactions as $trx)
                @php
                    $needsUpload    = $trx->status === 'pending' && !$trx->payment_proof;
                    $awaitingReview = $trx->status === 'pending' && $trx->payment_proof;
                    $isRejected     = $trx->status === 'rejected';
                    $isPaid         = $trx->status === 'paid';
                    $initial        = strtoupper(substr($trx->child?->name ?? '?', 0, 1));
                @endphp

                <div class="bg-surface border border-line rounded-2xl overflow-hidden" wire:key="trx-{{ $trx->id }}">
                    <div class="p-4">
                        {{-- Player + status --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center
                                            text-off font-extrabold text-sm flex-shrink-0">
                                    {{ $initial }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-navy leading-tight truncate">{{ $trx->child?->name ?? '—' }}</p>
                                    <p class="text-xs text-faint truncate">{{ $trx->package?->name ?? '—' }}</p>
                                    @if ($trx->package?->location)
                                        <p class="text-xs text-faint truncate">{{ $trx->package->location->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <x-badge :status="$trx->status" class="flex-shrink-0 mt-0.5">{{ __('messages.status.'.$trx->status) }}</x-badge>
                        </div>

                        {{-- Amount + transaction info --}}
                        <div class="flex items-end justify-between gap-2">
                            <div>
                                <p class="text-xl font-extrabold text-navy tracking-tight leading-none">
                                    Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </p>
                                <p class="text-3xs text-faint mt-1">
                                    {{ $trx->transaction_code }} · {{ $trx->created_at->format('d M Y') }}
                                </p>
                            </div>
                            {{-- No "Verified" line here: the badge beside the name
                                 already carries the status, and two indicators for
                                 one fact is noise. --}}
                            @if ($awaitingReview && $trx->payment_proof)
                                <a href="{{ Storage::disk('public')->url($trx->payment_proof) }}" target="_blank"
                                   class="text-xs font-semibold text-navy underline flex-shrink-0">{{ __('messages.payments.view_proof') }}</a>
                            @elseif ($isRejected && $trx->payment_proof)
                                <a href="{{ Storage::disk('public')->url($trx->payment_proof) }}" target="_blank"
                                   class="text-xs font-semibold text-navy underline flex-shrink-0">{{ __('messages.payments.view_proof') }}</a>
                            @endif
                        </div>

                        {{-- Admin rejection note --}}
                        @if ($trx->admin_notes)
                            <div class="mt-3 bg-[#FEF2F2] border border-[#FECACA] rounded-lg px-3 py-2">
                                <p class="text-xs text-[#B91C1C]">{{ $trx->admin_notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Action strip --}}
                    @if ($needsUpload)
                        <div class="bg-[#FFFBEB] border-t border-[#FDE68A] px-4 py-2.5 flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-[#92400E]">{{ __('messages.payments.upload_to_confirm') }}</p>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" wire:click="confirmCancel({{ $trx->id }})"
                                        class="bg-[#FEE2E2] text-[#B91C1C] text-2xs font-bold px-3 py-1.5 rounded-full
                                               hover:bg-[#FECACA] transition-colors">
                                    {{ __('messages.payments.cancel_action') }}
                                </button>
                                <button type="button" wire:click="openUpload({{ $trx->id }})"
                                        class="bg-navy text-off text-2xs font-bold px-3.5 py-1.5 rounded-full
                                               hover:bg-navy-2 transition-colors">
                                    {{ __('messages.payments.upload_action') }}
                                </button>
                            </div>
                        </div>
                    @elseif ($isRejected)
                        <div class="bg-[#FEF2F2] border-t border-[#FECACA] px-4 py-2.5 flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-[#9B1C1C]">{{ __('messages.payments.proof_rejected') }}</p>
                            <button type="button" wire:click="openUpload({{ $trx->id }})"
                                    class="flex-shrink-0 bg-[#B91C1C] text-white text-2xs font-bold px-3.5 py-1.5 rounded-full
                                           hover:bg-[#991B1B] transition-colors">
                                {{ __('messages.payments.reupload') }}
                            </button>
                        </div>
                    @elseif ($awaitingReview)
                        <div class="bg-[#FFFBEB] border-t border-[#FDE68A] px-4 py-2.5 flex items-center justify-between gap-3">
                            <p class="text-xs text-[#92400E] font-medium">⏳ {{ __('messages.payments.awaiting_verify') }}</p>
                            <button type="button" wire:click="confirmCancel({{ $trx->id }})"
                                    class="flex-shrink-0 bg-[#FEE2E2] text-[#B91C1C] text-2xs font-bold px-3 py-1.5 rounded-full
                                           hover:bg-[#FECACA] transition-colors">
                                {{ __('messages.payments.cancel_action') }}
                            </button>
                        </div>
                    @elseif ($isPaid)
                        <div class="bg-[#F0FDF4] border-t border-[#BBF7D0] px-4 py-2.5 flex items-center justify-end">
                            <a href="{{ route('parent.payments.receipt', $trx) }}"
                               class="inline-flex items-center gap-1.5 flex-shrink-0 bg-[#15803D] text-white text-2xs font-bold px-3.5 py-1.5 rounded-full hover:bg-[#166534] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                {{ __('messages.receipt.download') }}
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state :title="__('messages.payments.empty_title')" :description="__('messages.payments.empty_desc')" />
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($transactions->hasPages())
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @endif

    @endif

    {{-- ==================== CANCEL CONFIRMATION MODAL ==================== --}}
    @if ($confirmCancelId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-show="true"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-navy/50 backdrop-blur-sm" wire:click="dismissCancel"></div>

            {{-- Modal card --}}
            <div class="relative bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                {{-- Icon header --}}
                <div class="flex flex-col items-center text-center px-6 pt-7 pb-4">
                    <div class="w-14 h-14 bg-[#FEF2F2] rounded-full flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-[#B91C1C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-navy mb-1.5">{{ __('messages.payments.cancel_title') }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ __('messages.payments.cancel_body') }}
                        <span class="font-semibold text-gray-700">{{ __('messages.payments.cancel_warning') }}</span>
                    </p>
                </div>

                {{-- Transaction info --}}
                @if ($confirmCancelTransaction)
                <div class="mx-6 mb-5 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-navy rounded-full flex items-center justify-center text-white font-extrabold text-sm shrink-0">
                            {{ strtoupper(substr($confirmCancelTransaction->child?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-navy truncate">{{ $confirmCancelTransaction->child?->name ?? '—' }}</p>
                            <p class="text-2xs text-gray-400 truncate">{{ $confirmCancelTransaction->transaction_code }}</p>
                        </div>
                        <p class="text-sm font-extrabold text-navy shrink-0">
                            Rp {{ number_format($confirmCancelTransaction->amount, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
                @endif

                {{-- Action buttons --}}
                <div class="px-6 pb-6 grid grid-cols-2 gap-2.5">
                    <button type="button" wire:click="dismissCancel"
                            class="py-3 rounded-xl border border-gray-200 bg-white text-navy text-sm font-bold
                                   hover:bg-gray-50 transition-colors">
                        {{ __('messages.payments.keep_btn') }}
                    </button>
                    <button type="button" wire:click="cancelTransaction" wire:loading.attr="disabled"
                            class="py-3 rounded-xl bg-[#B91C1C] text-white text-sm font-bold
                                   hover:bg-[#991B1B] transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="cancelTransaction">{{ __('messages.payments.yes_cancel') }}</span>
                        <span wire:loading wire:target="cancelTransaction">{{ __('messages.payments.processing') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
