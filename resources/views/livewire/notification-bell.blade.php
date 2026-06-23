<div class="relative" x-data="{ open: @entangle('isOpen') }">

    {{-- Bell button --}}
    <button wire:click="toggle"
            class="relative p-2 rounded-lg hover:bg-off text-muted hover:text-navy transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-[#B91C1C] text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         @click.outside="$wire.close()"
         class="absolute right-0 top-full mt-2 w-80 bg-surface border border-line rounded-2xl shadow-xl z-50 overflow-hidden"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-line">
            <h3 class="text-sm font-extrabold uppercase tracking-tight text-navy">Notifications</h3>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead"
                        class="text-xs text-navy font-semibold hover:underline">
                    Mark all read
                </button>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-[360px] overflow-y-auto divide-y divide-line">
            @forelse ($notifications as $notif)
                <div wire:key="notif-{{ $notif->id }}"
                     @class([
                         'flex gap-3 px-4 py-3 transition-colors',
                         'bg-navy/5' => !$notif->is_read,
                         'hover:bg-off' => $notif->is_read,
                     ])>
                    {{-- Icon dot --}}
                    <div class="mt-1 shrink-0">
                        @if (!$notif->is_read)
                            <span class="w-2 h-2 rounded-full bg-navy block"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-line block"></span>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-ink leading-snug">{{ $notif->title }}</p>
                        <p class="text-xs text-muted mt-0.5 leading-relaxed">{{ $notif->body }}</p>
                        <p class="text-[10px] text-faint mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Mark read --}}
                    @if (!$notif->is_read)
                        <button wire:click="markRead({{ $notif->id }})"
                                class="shrink-0 mt-1 text-faint hover:text-navy transition-colors" title="Mark as read">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="w-8 h-8 mx-auto text-line mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-xs text-faint">No notifications yet</p>
                </div>
            @endforelse
        </div>

        @if ($notifications->isNotEmpty())
            <div class="border-t border-line px-4 py-2.5 text-center">
                <p class="text-xs text-faint">Showing last {{ $notifications->count() }} notifications</p>
            </div>
        @endif
    </div>
</div>
