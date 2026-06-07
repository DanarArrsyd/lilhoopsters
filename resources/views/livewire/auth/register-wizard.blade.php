<div>
    {{-- Progress --}}
    <div class="flex items-center gap-2 mb-6">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex-1 h-1 rounded-full {{ $i <= $step ? 'bg-orange-500' : 'bg-slate-200' }}"></div>
        @endfor
    </div>
    <p class="text-xs text-slate-400 mb-6">Langkah {{ $step }} dari {{ $totalSteps }}</p>

    {{-- Step 1: Account --}}
    @if ($step === 1)
        <h2 class="text-xl font-bold text-slate-900 mb-1">Buat Akun</h2>
        <p class="text-sm text-slate-500 mb-6">Informasi login Anda</p>
        <div class="space-y-4">
            <x-input wire:model="name" label="Nama Lengkap" placeholder="Budi Santoso" required :error="$errors->first('name')" />
            <x-input wire:model="email" type="email" label="Email" placeholder="email@contoh.com" required :error="$errors->first('email')" />
            <x-input wire:model="password" type="password" label="Password" placeholder="Min. 8 karakter" required :error="$errors->first('password')" />
            <x-input wire:model="password_confirmation" type="password" label="Konfirmasi Password" placeholder="Ulangi password" required :error="$errors->first('password_confirmation')" />
        </div>
        <x-btn wire:click="nextStep" class="w-full justify-center mt-6">Lanjut →</x-btn>
    @endif

    {{-- Step 2: Parent Info --}}
    @if ($step === 2)
        <h2 class="text-xl font-bold text-slate-900 mb-1">Info Orang Tua</h2>
        <p class="text-sm text-slate-500 mb-6">Informasi kontak Anda</p>
        <div class="space-y-4">
            <x-input wire:model="whatsapp_number" label="Nomor WhatsApp" placeholder="08xxxxxxxxxx" required :error="$errors->first('whatsapp_number')" helper="Digunakan untuk notifikasi penting" />
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Alamat <span class="text-slate-400 font-normal">(opsional)</span></label>
                <textarea wire:model="address" rows="2" placeholder="Jl. Contoh No. 1, Jakarta"
                          class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"></textarea>
            </div>
            <x-input wire:model="occupation" label="Pekerjaan" placeholder="Contoh: Pengusaha" :error="$errors->first('occupation')" />
        </div>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1 justify-center">← Kembali</x-btn>
            <x-btn wire:click="nextStep" class="flex-1 justify-center">Lanjut →</x-btn>
        </div>
    @endif

    {{-- Step 3: First Child --}}
    @if ($step === 3)
        <h2 class="text-xl font-bold text-slate-900 mb-1">Data Anak Pertama</h2>
        <p class="text-sm text-slate-500 mb-6">Anda bisa tambah lebih banyak anak setelah masuk</p>
        <div class="space-y-4">
            <x-input wire:model="child_name" label="Nama Anak" placeholder="Rafi Santoso" required :error="$errors->first('child_name')" />
            <x-input wire:model="child_birth_date" type="date" label="Tanggal Lahir" required :error="$errors->first('child_birth_date')" />
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Jenis Kelamin <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                        <input type="radio" wire:model="child_gender" value="male" class="text-orange-500"> Laki-laki
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                        <input type="radio" wire:model="child_gender" value="female" class="text-orange-500"> Perempuan
                    </label>
                </div>
                @error('child_gender') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <x-input wire:model="child_school" label="Sekolah" placeholder="TK Harapan Bangsa" :error="$errors->first('child_school')" />
        </div>
        <x-alert type="info" class="mt-4 text-xs">
            Setelah mendaftar, admin akan memverifikasi akun Anda dalam 1–2 hari kerja.
        </x-alert>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1 justify-center">← Kembali</x-btn>
            <x-btn wire:click="submit" class="flex-1 justify-center" wire:loading.attr="disabled">
                <span wire:loading.remove>Daftar Sekarang</span>
                <span wire:loading>Mendaftar...</span>
            </x-btn>
        </div>
    @endif
</div>
