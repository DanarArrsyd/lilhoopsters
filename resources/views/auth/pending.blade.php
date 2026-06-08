<x-auth title="You're Almost In!">
    <div class="text-center">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 mb-2">You're Almost In!</h2>
        <p class="text-sm text-slate-500 mb-6">
            Our team is reviewing your registration.<br>
            We'll get back to you within <strong>1–2 business days</strong>.
        </p>
        <x-alert type="info" class="text-left mb-6">
            We'll send you a WhatsApp message once your account is approved. 🏀
        </x-alert>
        <p class="text-xs text-slate-400">
            Need help? Reach us on WhatsApp<br>
            <a href="https://wa.me/6281770212177" class="text-orange-500 font-medium" target="_blank">+62 817-7021-2177</a>
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-btn type="submit" variant="secondary" class="w-full justify-center">Sign Out</x-btn>
        </form>
    </div>
</x-auth>
