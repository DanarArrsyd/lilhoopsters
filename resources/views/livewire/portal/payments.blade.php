<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
            <p class="text-sm text-slate-500">Your transaction history and payment uploads</p>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filter --}}
    <x-card class="mb-4" padding="p-4">
        <div class="w-44">
            <select wire:model.live="filterStatus"
                    class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="rejected">Rejected</option>
                <option value="expired">Expired</option>
            </select>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Transaction</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Player / Package</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Proof</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($transactions as $trx)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <p class="font-mono text-xs font-semibold text-slate-700">{{ $trx->transaction_code }}</p>
                            <p class="text-xs text-slate-400">{{ $trx->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-900">{{ $trx->child?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $trx->package?->name ?? '—' }}</p>
                            @if ($trx->package?->location)
                                <p class="text-xs text-slate-400">{{ $trx->package->location->name }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            Rp {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4">
                            @if ($trx->payment_proof)
                                <a href="{{ Storage::url($trx->payment_proof) }}" target="_blank"
                                   class="text-orange-500 hover:text-orange-600 text-xs font-medium underline">
                                    View Proof
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <x-badge :status="$trx->status">{{ ucfirst($trx->status) }}</x-badge>
                            @if ($trx->admin_notes)
                                <p class="text-xs text-red-500 mt-1">{{ $trx->admin_notes }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            @if ($trx->status === 'pending' && !$trx->payment_proof)
                                <x-btn variant="primary" size="sm" wire:click="openUpload({{ $trx->id }})">
                                    Upload Proof
                                </x-btn>
                            @elseif ($trx->status === 'rejected')
                                <x-btn variant="secondary" size="sm" wire:click="openUpload({{ $trx->id }})">
                                    Re-upload
                                </x-btn>
                            @elseif ($trx->status === 'pending' && $trx->payment_proof)
                                <span class="text-xs text-slate-400">Awaiting review</span>
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
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Upload modal --}}
    @if ($uploadingId)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Upload Payment Proof</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Image file, max 5MB (JPG, PNG, WEBP)</p>
                </div>

                <div class="p-6 space-y-4">
                    {{-- File input --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Bukti Transfer <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="file" wire:model="proofFile" accept="image/*"
                                   class="block w-full text-sm text-slate-500 border border-slate-200 rounded-lg cursor-pointer
                                          file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0
                                          file:text-sm file:font-medium file:bg-orange-50 file:text-orange-600
                                          hover:file:bg-orange-100 focus:outline-none" />
                        </div>
                        @error('proofFile') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="proofFile" class="text-xs text-slate-400">Uploading...</div>

                        @if ($proofFile && !$errors->has('proofFile'))
                            <div class="mt-2">
                                <img src="{{ $proofFile->temporaryUrl() }}" alt="Preview"
                                     class="h-32 w-auto rounded-lg object-cover border border-slate-200">
                            </div>
                        @endif
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                        <x-input wire:model="paymentMethod" placeholder="e.g. BCA Transfer, GoPay..." />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Notes (optional)</label>
                        <textarea wire:model="paymentNotes" rows="2"
                                  class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                  placeholder="e.g. Transfer sudah dilakukan pagi ini..."></textarea>
                    </div>
                </div>

                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="cancelUpload">Cancel</x-btn>
                    <x-btn variant="primary" class="flex-1 justify-center" wire:click="uploadProof"
                           wire:loading.attr="disabled" wire:target="uploadProof">
                        <span wire:loading.remove wire:target="uploadProof">Submit Proof</span>
                        <span wire:loading wire:target="uploadProof">Uploading...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
