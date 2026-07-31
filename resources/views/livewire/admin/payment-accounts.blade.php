<div class="mt-6">
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line flex items-center justify-between gap-3">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.admin.payment_accounts.title') }}</p>
            @if (!$showForm)
                <button type="button" wire:click="openCreate"
                        class="flex items-center gap-1.5 text-xs font-bold text-navy bg-off border border-line px-3 py-1.5 rounded-lg hover:bg-navy hover:text-off transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('messages.admin.payment_accounts.add_account') }}
                </button>
            @endif
        </div>

        @if (session('accounts_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('accounts_success') }}</x-alert>
            </div>
        @endif

        {{-- Add / Edit form --}}
        @if ($showForm)
            <div class="p-6 border-b border-line space-y-4">
                <p class="text-xs font-semibold text-navy uppercase tracking-wide">
                    {{ $editingId ? __('messages.admin.payment_accounts.edit_account') : __('messages.admin.payment_accounts.new_account') }}
                </p>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Type --}}
                    <div class="col-span-2 sm:col-span-1">
                        <x-select wire:model="type" required
                                  label="{{ __('messages.admin.payment_accounts.label_type') }}"
                                  :error="$errors->first('type')">
                            <option value="bank">{{ __('messages.admin.payment_accounts.type_bank') }}</option>
                            <option value="ewallet">{{ __('messages.admin.payment_accounts.type_ewallet') }}</option>
                        </x-select>
                    </div>

                    {{-- Bank / Wallet Name --}}
                    <div class="space-y-1.5 col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                            {{ $type === 'ewallet' ? __('messages.admin.payment_accounts.label_wallet_name') : __('messages.admin.payment_accounts.label_bank_name') }}
                            <span class="text-[#B91C1C]">*</span>
                        </label>
                        <x-input wire:model="bankName"
                                 placeholder="{{ $type === 'ewallet' ? 'e.g. GoPay, OVO, DANA' : 'e.g. BCA, BRI, Mandiri' }}" />
                        @error('bankName') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    {{-- Account Number --}}
                    <div class="space-y-1.5 col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                            {{ $type === 'ewallet' ? __('messages.admin.payment_accounts.label_phone_number') : __('messages.admin.payment_accounts.label_acct_number') }}
                            <span class="text-[#B91C1C]">*</span>
                        </label>
                        <x-input wire:model="accountNumber" placeholder="e.g. 0812 3456 7890" />
                        @error('accountNumber') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    {{-- Account Holder --}}
                    <div class="space-y-1.5 col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.payment_accounts.label_acct_holder') }} <span class="text-[#B91C1C]">*</span></label>
                        <x-input wire:model="accountHolder" placeholder="Full name on account" />
                        @error('accountHolder') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <div class="col-span-2 flex items-center gap-2.5">
                        <button type="button" wire:click="$toggle('isActive')"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200
                                       {{ $isActive ? 'bg-navy' : 'bg-gray-200' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200
                                         {{ $isActive ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-sm font-medium text-ink">{{ __('messages.admin.payment_accounts.label_active') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 pt-2">
                    <x-btn wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.admin.payment_accounts.add_account') }}</span>
                        <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                    </x-btn>
                    <button type="button" wire:click="cancelForm"
                            class="px-4 py-2 text-sm font-semibold text-navy border border-line rounded-xl hover:bg-off transition-colors">
                        {{ __('messages.common.cancel') }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Account list --}}
        @if ($accounts->isEmpty() && !$showForm)
            <div class="px-6 py-8 text-center">
                <p class="text-sm text-faint">{{ __('messages.admin.payment_accounts.empty_title') }}</p>
                <p class="text-xs text-faint mt-1">{{ __('messages.admin.payment_accounts.empty_desc') }}</p>
            </div>
        @else
            <div class="divide-y divide-line">
                @foreach ($accounts as $acc)
                    <div class="px-6 py-4 flex items-center gap-4" wire:key="acc-{{ $acc->id }}">
                        {{-- Icon --}}
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                    {{ $acc->type === 'ewallet' ? 'bg-purple-100' : 'bg-blue-50' }}">
                            @if ($acc->type === 'ewallet')
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-navy">{{ $acc->bank_name }}</p>
                            <p class="text-xs text-muted">{{ $acc->account_number }} · {{ $acc->account_holder }}</p>
                            <span class="inline-block text-3xs font-bold uppercase tracking-wide px-1.5 py-0.5 rounded
                                         {{ $acc->type === 'ewallet' ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ $acc->type === 'ewallet' ? __('messages.admin.payment_accounts.type_ewallet') : __('messages.admin.payment_accounts.type_bank') }}
                            </span>
                        </div>

                        {{-- Active badge + controls --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if (!$acc->is_active)
                                <span class="text-3xs font-bold text-faint bg-off border border-line px-2 py-0.5 rounded-full">
                                    {{ __('messages.admin.payment_accounts.hidden_badge') }}
                                </span>
                            @endif
                            <button type="button" wire:click="openEdit({{ $acc->id }})"
                                    title="{{ __('messages.common.edit') }}"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-navy hover:bg-off transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button type="button" wire:click="delete({{ $acc->id }})"
                                    wire:confirm="Remove this payment account?"
                                    title="{{ __('messages.common.delete') }}"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-[#B91C1C] hover:bg-[#FEF2F2] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>
