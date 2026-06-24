<x-app title="Sign In">

<div class="min-h-[100dvh] flex flex-col bg-white">

    {{-- Logo top-left --}}
    <div class="fixed top-5 left-6 z-20 flex items-center gap-2.5">
        <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-xl object-cover">
        <span class="text-navy font-extrabold text-sm uppercase tracking-tight">Lil' Hoopsters</span>
    </div>

    {{-- Centered form --}}
    <div class="flex-1 flex items-center justify-center px-6 py-24">
        <div class="w-full max-w-xl">

            {{-- Heading --}}
            <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-2">
                Welcome back.
            </h1>
            <p class="text-sm text-gray-400 mb-10">Sign in to your Lil' Hoopsters account.</p>

            {{-- Google --}}
            <a href="{{ route('auth.google') }}"
               class="flex items-center justify-center gap-2.5 w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-600 hover:border-gray-300 hover:bg-gray-50 transition-colors mb-6">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </a>

            {{-- Divider --}}
            <div class="flex items-center gap-4 mb-8">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-xs text-gray-300 uppercase tracking-widest">or</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            @if (session('error'))
                <div class="mb-6 flex items-center gap-2 text-sm text-[#DC2626]">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-8">
                @csrf

                {{-- Email --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Email</span>
                        <span class="text-[#DC2626] text-sm font-bold">*</span>
                    </div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                        autocomplete="email"
                        autofocus
                        class="w-full border-0 border-b-2 {{ $errors->first('email') ? 'border-[#DC2626]' : 'border-gray-200' }} focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-[#DC2626] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Password</span>
                        <span class="text-[#DC2626] text-sm font-bold">*</span>
                    </div>
                    <div x-data="{ show: false }" class="relative">
                        <input
                            :type="show ? 'text' : 'password'"
                            name="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                            class="w-full border-0 border-b-2 {{ $errors->first('password') ? 'border-[#DC2626]' : 'border-gray-200' }} focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 pr-10 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                        >
                        <button type="button" @click="show = !show" tabindex="-1"
                            class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-300 hover:text-gray-500 transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-[#DC2626] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember me + submit --}}
                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 accent-navy"> Remember me
                    </label>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-6 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Sign In
                    </button>
                </div>

            </form>

        </div>
    </div>

    {{-- Bottom: register + trial links --}}
    <div class="fixed bottom-6 left-6 z-20 flex flex-col gap-1.5">
        <a href="{{ route('register') }}" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
            New here? <span class="font-semibold">Create an account</span>
        </a>
        <a href="{{ route('trial') }}" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
            Not a member yet? <span class="font-semibold">Book a free trial</span>
        </a>
    </div>

</div>

</x-app>
