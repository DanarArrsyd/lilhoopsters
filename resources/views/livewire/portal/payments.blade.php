<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Payments</h2>
        <p class="text-sm text-muted">Your transaction history and payment uploads.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filter --}}
    <x-card class="mb-4" padding="p-4">
        <div class="w-full sm:w-44">
            <x-select wire:model.live="filterStatus">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="rejected">Rejected</option>
                <option value="expired">Expired</option>
            </x-select>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Transaction</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Player / Package</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Amount</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Proof</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $trx)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <p class="font-mono text-xs font-semibold text-ink">{{ $trx->transaction_code }}</p>
                                <p class="text-xs text-faint">{{ $trx->created_at->format('d M Y') }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $trx->child?->name ?? '—' }}</p>
                                <p class="text-xs text-faint">{{ $trx->package?->name ?? '—' }}</p>
                                @if ($trx->package?->location)
                                    <p class="text-xs text-faint">{{ $trx->package->location->name }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-semibold text-ink">
                                Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($trx->payment_proof)
                                    <a href="{{ Storage::url($trx->payment_proof) }}" target="_blank"
                                       class="text-navy hover:underline text-xs font-semibold">
                                        View Proof
                                    </a>
                                @else
                                    <span class="text-faint text-xs">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$trx->status">{{ ucfirst($trx->status) }}</x-badge>
                                @if ($trx->admin_notes)
                                    <p class="text-xs text-[#B91C1C] mt-1">{{ $trx->admin_notes }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if ($trx->status === 'pending' && !$trx->payment_proof)
                                    <x-btn variant="primary" size="sm" wire:click="openUpload({{ $trx->id }})">
                                        Upload Proof
                                    </x-btn>
                                @elseif ($trx->status === 'rejected')
                                    <x-btn variant="ghost" size="sm" wire:click="openUpload({{ $trx->id }})">
                                        Re-upload
                                    </x-btn>
                                @elseif ($trx->status === 'pending' && $trx->payment_proof)
                                    <span class="text-xs text-faint">Awaiting review</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No transactions yet" description="Enroll a player to generate your first transaction." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Upload modal --}}
    @if ($uploadingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancelUpload"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <div>
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Upload Payment Proof</h3>
                    <p class="text-xs text-faint mt-0.5">Image file, max 5MB (JPG, PNG, WEBP)</p>
                </div>
                <button wire:click="cancelUpload" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Payment Proof <span class="text-[#B91C1C]">*</span></label>
                    <input type="file" wire:model="proofFile" accept="image/*"
                           class="block w-full text-sm text-muted border border-line rounded-xl cursor-pointer
                                  file:mr-3 file:py-2 file:px-4 file:rounded-l-xl file:border-0
                                  file:text-sm file:font-semibold file:bg-navy/8 file:text-navy
                                  hover:file:bg-navy/12 focus:outline-none" />
                    @error('proofFile') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="proofFile" class="text-xs text-faint">Uploading...</div>
                    @if ($proofFile && !$errors->has('proofFile'))
                        <div class="mt-2">
                            <img src="{{ $proofFile->temporaryUrl() }}" alt="Preview"
                                 class="h-32 w-auto rounded-xl object-cover border border-line">
                        </div>
                    @endif
                </div>
                <x-input wire:model="paymentMethod" label="Payment Method" placeholder="e.g. BCA Transfer, GoPay..." />
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Notes (optional)</label>
                    <textarea wire:model="paymentNotes" rows="2" aria-label="Payment notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="e.g. Transfer completed this morning..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelUpload">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="uploadProof" wire:loading.attr="disabled" wire:target="uploadProof">
                    <span wire:loading.remove wire:target="uploadProof">Submit Proof</span>
                    <span wire:loading wire:target="uploadProof">Uploading...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
