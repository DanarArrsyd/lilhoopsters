<x-auth title="Halaman Tidak Ditemukan">
    <div class="text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">404</h2>
        <p class="text-sm font-medium text-slate-700 mb-1">Halaman Tidak Ditemukan</p>
        <p class="text-sm text-slate-400 mb-6">Halaman yang Anda cari tidak ada atau telah dipindahkan.</p>
        <x-btn href="{{ route('login') }}">Ke Halaman Utama</x-btn>
    </div>
</x-auth>
