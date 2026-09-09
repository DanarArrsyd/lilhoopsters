<x-auth title="Verify Your Email">
    <div class="text-center">
        <div class="w-16 h-16 bg-navy rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-off" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy mb-2">Check Your Email</h2>
        <p class="text-sm text-muted mb-6">
            We've sent a verification link to your email address.<br>
            Please click it to activate your account.
        </p>

        @if (session('status') === 'verification-link-sent')
            <x-alert type="success" class="text-left mb-6">
                A new verification link has been sent to your email address.
            </x-alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
            @csrf
            <x-btn type="submit" variant="primary" class="w-full">Resend Verification Email</x-btn>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-btn type="submit" variant="secondary" class="w-full">Sign Out</x-btn>
        </form>
    </div>
</x-auth>
