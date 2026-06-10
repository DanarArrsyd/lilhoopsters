<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <div class="flex min-h-[100dvh]">
        {{-- Sidebar --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line flex flex-col
                      transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

            {{-- Logo --}}
            <div class="h-16 flex items-center gap-3 px-4 border-b border-line">
                <div class="w-9 h-9 bg-navy rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-off" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-navy font-extrabold text-sm uppercase tracking-tight leading-tight">BasketManage</p>
                    <p class="text-faint text-[10px] uppercase tracking-wide">Parent Portal</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                {{ $navigation ?? '' }}
            </nav>

            {{-- User --}}
            <div class="border-t border-line p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-navy rounded-full flex items-center justify-center text-off font-bold text-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-ink text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-muted text-xs truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-muted hover:text-[#B91C1C] transition-colors p-1" title="Sign out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Overlay mobile --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-navy/40 z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0 lg:ml-60">
            {{-- Topbar --}}
            <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-4 sticky top-0 z-30">
                <button class="lg:hidden p-2 rounded-lg hover:bg-off text-muted" onclick="openSidebar()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1">
                    <h1 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                {{ $actions ?? '' }}
            </header>

            {{-- Content --}}
            <main class="flex-1 bg-off p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @push('scripts')
    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    </script>
    @endpush
</x-app>
