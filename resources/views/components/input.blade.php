@props(['label' => null, 'error' => null, 'helper' => null])

<div class="space-y-1">
    @if ($label)
        <label class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-red-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    <input {{ $attributes->merge([
        'class' => 'block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm
                    bg-white text-slate-900 placeholder:text-slate-400
                    focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500
                    disabled:bg-slate-50 disabled:text-slate-400
                    ' . ($error ? 'border-red-400 focus:ring-red-200 focus:border-red-400' : ''),
    ]) }}>

    @if ($error)
        <p class="text-xs text-red-500">{{ $error }}</p>
    @elseif ($helper)
        <p class="text-xs text-slate-400">{{ $helper }}</p>
    @endif
</div>
