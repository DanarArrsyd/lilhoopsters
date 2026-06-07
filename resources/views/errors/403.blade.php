<x-auth title="Akses Ditolak">
    <div class="text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">403</h2>
        <p class="text-sm font-medium text-slate-700 mb-1">Akses Ditolak</p>
        <p class="text-sm text-slate-400 mb-6">{{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}</p>
        <x-btn href="{{ url()->previous(fallback: route('login')) }}" variant="secondary">← Kembali</x-btn>
    </div>
</x-auth>
