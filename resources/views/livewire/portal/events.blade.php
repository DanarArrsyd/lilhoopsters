<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.events.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.pages.events.subtitle') }}</p>
    </div>

    @if (session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="danger">{{ session('error') }}</x-alert>
    @endif

    {{-- My registrations --}}
    @if ($myRegistrations->isNotEmpty())
        <x-card padding="p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-muted mb-3">{{ __('messages.events.my_registrations') }}</p>
            <div class="divide-y divide-line">
                @foreach ($myRegistrations as $reg)
                    @php
                        $sb = match ($reg->status) {
                            'confirmed' => 'bg-[#15803D]/10 text-[#15803D]',
                            'pending'   => 'bg-[#B45309]/10 text-[#B45309]',
                            default     => 'bg-line text-faint',
                        };
                    @endphp
                    <div class="py-2.5 flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink">{{ $reg->event?->name }}</p>
                            <p class="text-xs text-muted">{{ $reg->child?->name }}</p>
                        </div>
                        <span class="inline-flex text-2xs font-bold px-2 py-0.5 rounded-md {{ $sb }}">{{ __('messages.status.'.$reg->status) }}</span>
                        @if ($reg->status === 'pending' && $reg->transaction)
                            <a href="{{ route('parent.home') }}" class="text-2xs font-semibold text-navy hover:underline shrink-0">{{ __('messages.events.pay_now') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- Open events --}}
    @forelse ($events as $event)
        @php
            $full = $event->capacity !== null && $event->taken_count >= $event->capacity;
        @endphp
        <x-card padding="p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-extrabold text-navy">{{ $event->name }}</h3>
                    <p class="text-xs text-muted mt-0.5">
                        {{ $event->start_date->format('d M') }} – {{ $event->end_date->format('d M Y') }}
                        @if ($event->location) · {{ $event->location->name }} @endif
                    </p>
                    @if ($event->description)
                        <p class="text-sm text-muted mt-2 max-w-prose">{{ $event->description }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-extrabold text-navy">{{ $event->isPaid() ? 'Rp ' . number_format($event->price, 0, ',', '.') : __('messages.events.free') }}</p>
                    @if ($event->capacity)
                        <p class="text-2xs text-faint">{{ __('messages.events.spots_left', ['n' => max(0, $event->capacity - $event->taken_count)]) }}</p>
                    @endif
                </div>
            </div>

            <div class="border-t border-line mt-4 pt-3">
                @if ($full)
                    <p class="text-sm text-[#B45309] font-semibold">{{ __('messages.events.full') }}</p>
                @elseif ($children->isEmpty())
                    <p class="text-sm text-muted">{{ __('messages.events.no_children') }}</p>
                @else
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 max-w-xs space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.events.child') }}</label>
                            <x-select wire:model="childSelection.{{ $event->id }}">
                                <option value="">{{ __('messages.events.select_child') }}</option>
                                @foreach ($children as $c)
                                    <option value="{{ $c->id }}"
                                        @disabled(in_array($event->id . '-' . $c->id, $registeredPairs, true))>
                                        {{ $c->name }}@if(in_array($event->id . '-' . $c->id, $registeredPairs, true)) {{ __('messages.events.registered') }}@endif
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-btn wire:click="register({{ $event->id }})" wire:loading.attr="disabled" wire:target="register({{ $event->id }})">
                            {{ __('messages.events.register') }}
                        </x-btn>
                    </div>
                @endif
            </div>
        </x-card>
    @empty
        <x-empty-state :title="__('messages.events.empty_title')" :description="__('messages.events.empty_desc')" />
    @endforelse
</div>
