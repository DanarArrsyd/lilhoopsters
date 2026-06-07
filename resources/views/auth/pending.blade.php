<x-auth title="Menunggu Verifikasi">
    <div class="text-center">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 mb-2">Menunggu Verifikasi</h2>
        <p class="text-sm text-slate-500 mb-6">
            Pendaftaran Anda sedang ditinjau oleh admin.<br>
            Proses verifikasi biasanya memakan waktu <strong>1–2 hari kerja</strong>.
        </p>
        <x-alert type="info" class="text-left mb-6">
            Anda akan mendapat notifikasi melalui WhatsApp saat akun sudah disetujui.
        </x-alert>
        <p class="text-xs text-slate-400">
            Butuh bantuan? Hubungi kami via WhatsApp<br>
            <a href="https://wa.me/6281770212177" class="text-orange-500 font-medium" target="_blank">+62 817-7021-2177</a>
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-btn type="submit" variant="secondary" class="w-full justify-center">Logout</x-btn>
        </form>
    </div>
</x-auth>
