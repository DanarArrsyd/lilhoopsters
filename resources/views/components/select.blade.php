{{--
    Select — a styled listbox wrapped around a real <select>.

    The native element stays in the DOM and remains the source of truth, so every
    call site keeps working unchanged: wire:model, wire:change, wire:key, name,
    required, disabled and server-side validation all bind to it exactly as
    before. Alpine only replaces the OS-rendered popup, which can't be styled and
    reads badly on a dense admin form.

    Without JS the native select renders as a normal styled field — the enhanced
    trigger stays hidden until Alpine marks the wrapper — so the form never breaks.
--}}
@props([
    'label'   => null,
    'error'   => null,
    'helper'  => null,
    'loading' => false,
    'success' => null,
    // field = full form control · bare = borderless (segmented pickers)
    // chip  = compact inline pill (table cells)
    'variant'      => 'field',
    'triggerClass' => '',
    // null = auto (filter appears past 8 options). Force with true/false — a
    // 12-entry minute picker doesn't want a search box.
    'searchable'   => null,
])

@php
    $fieldId = $attributes->get('id') ?? $attributes->get('name') ?? 'sel_' . \Illuminate\Support\Str::random(6);
    $descId  = $fieldId . '_desc';
    $hasDesc = $error || $success || $helper;

    $stateBorder = match (true) {
        (bool) $error   => 'border-danger',
        (bool) $success => 'border-success',
        default         => 'border-line',
    };

    $triggerBase = match ($variant) {
        'bare' => 'w-full flex items-center justify-center gap-1 rounded-lg px-1.5 py-1 text-sm font-semibold text-center
                   bg-transparent border-0 transition-colors duration-150 hover:bg-off
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-navy/40
                   disabled:text-faint disabled:cursor-not-allowed',
        'chip' => 'inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold
                   border-0 transition-colors duration-150
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-navy/40
                   disabled:opacity-60 disabled:cursor-not-allowed',
        default => 'w-full flex items-center gap-2 rounded-xl px-3.5 py-3 text-left text-sm
                    bg-surface border ' . $stateBorder . ' transition-colors duration-150
                    hover:border-navy/40
                    focus:outline-none focus-visible:border-navy focus-visible:ring-2 focus-visible:ring-navy/40
                    disabled:bg-off disabled:text-faint disabled:cursor-not-allowed disabled:hover:border-line',
    };

    $openClass = $variant === 'field' ? 'border-navy ring-2 ring-navy/20' : 'ring-2 ring-navy/30';

    $nativeBase = $variant === 'field'
        ? 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink border ' . $stateBorder
        : 'block w-full bg-transparent text-sm text-ink';

    $threshold = match (true) {
        $searchable === true  => 0,
        $searchable === false => 9999,
        default               => 8,
    };

    $wrapperClass = $variant === 'chip' ? 'inline-block' : 'space-y-1.5';

    // Numeric pickers centre their value in the trigger, so the list has to
    // centre too or the digits jump sideways when the panel opens.
    $centreOptions = $variant === 'bare';
@endphp

<div class="{{ $wrapperClass }}"
     x-data="hoopSelect({ loading: {{ $loading ? 'true' : 'false' }}, searchThreshold: {{ $threshold }} })"
     x-id="['hoop-select']"
     @keydown.escape.window="close()"
     @pointerdown.window="onDocumentPointer($event)">

    @if ($label)
        {{-- `for` targets the native select so the label still works without JS;
             once enhanced, clicking it hands focus to the visible trigger. --}}
        <label id="{{ $fieldId }}_label" for="{{ $fieldId }}"
               @click.prevent="$refs.trigger.focus()"
               class="block text-xs font-semibold uppercase tracking-wide text-navy">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-danger ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{-- Source of truth. Visually hidden once Alpine flags the wrapper enhanced.
         The x-bind attributes only land when Alpine boots, so an unenhanced page
         keeps a fully tabbable, screen-reader-visible native select — while an
         enhanced one exposes exactly one combobox to AT, not two. --}}
    <select x-ref="native"
            x-bind:tabindex="'-1'"
            x-bind:aria-hidden="'true'"
            {{ $attributes->merge([
                'id'    => $fieldId,
                'class' => 'hoop-native ' . $nativeBase,
            ]) }}
            @if ($hasDesc) aria-describedby="{{ $descId }}" @endif
            @if ($error) aria-invalid="true" @endif>
        {{ $slot }}
    </select>

    {{-- ── Trigger ── --}}
    <button type="button"
            x-ref="trigger"
            class="hoop-trigger hoop-trigger--{{ $variant }} group {{ $triggerBase }} {{ $triggerClass }}"
            :class="open && '{{ $openClass }}'"
            role="combobox"
            aria-haspopup="listbox"
            :aria-expanded="open"
            :aria-controls="$id('hoop-select') + '-list'"
            :aria-activedescendant="activeDescendant"
            aria-busy="{{ $loading ? 'true' : 'false' }}"
            :disabled="isDisabled"
            aria-labelledby="@if($label){{ $fieldId }}_label @endif{{ $fieldId }}_value"
            @if ($hasDesc) aria-describedby="{{ $descId }}" @endif
            @click="toggle()"
            @keydown.enter.prevent="open ? choose(visibleOptions[activeIndex]) : show()"
            @keydown.space.prevent="open ? choose(visibleOptions[activeIndex]) : show()"
            @keydown.arrow-down.prevent="open ? move(1) : show()"
            @keydown.arrow-up.prevent="open ? move(-1) : show()"
            @keydown.home.prevent="open && moveTo('first')"
            @keydown.end.prevent="open && moveTo('last')"
            @keydown.tab="close(false)"
            @keypress="typeAhead($event.key)">

        <span id="{{ $fieldId }}_value"
              class="min-w-0 truncate {{ $variant === 'field' ? 'flex-1' : '' }}"
              @if ($variant === 'field') :class="showsPlaceholder ? 'text-muted' : 'text-ink font-medium'" @endif
              x-text="triggerLabel"></span>

        @if ($loading)
            <svg class="w-4 h-4 shrink-0 text-navy animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
        @elseif ($success)
            <svg class="w-4 h-4 shrink-0 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        @else
            <svg class="w-4 h-4 shrink-0 text-muted transition-transform duration-150 group-hover:text-navy"
                 :class="open && '-rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        @endif
    </button>

    {{-- ── Panel ──
         Teleported to <body>: several of these selects sit inside modal cards
         with overflow-hidden, which would clip an absolutely positioned popup. --}}
    <template x-teleport="body">
        <div x-ref="panel"
             x-show="open"
             x-cloak
             class="hoop-panel z-50 overflow-y-auto rounded-xl border border-line bg-surface shadow-lg shadow-navy/10"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.prevent.stop="close()">

            {{-- Filter field, only past the threshold — short lists stay one glance --}}
            <template x-if="searchable">
                <div class="sticky top-0 z-10 flex items-center gap-2 border-b border-line bg-surface px-3 py-2.5">
                    <svg class="w-3.5 h-3.5 shrink-0 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input x-ref="search"
                           x-model="query"
                           @input="activeIndex = 0"
                           type="text"
                           class="w-full bg-transparent text-sm text-ink placeholder:text-muted focus:outline-none"
                           placeholder="{{ __('messages.common.filter_options') }}"
                           role="combobox"
                           aria-expanded="true"
                           aria-autocomplete="list"
                           :aria-controls="$id('hoop-select') + '-list'"
                           :aria-activedescendant="activeDescendant"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.home.prevent="moveTo('first')"
                           @keydown.end.prevent="moveTo('last')"
                           @keydown.enter.prevent="choose(visibleOptions[activeIndex])"
                           @keydown.tab="close(false)">
                </div>
            </template>

            <ul :id="$id('hoop-select') + '-list'" role="listbox" class="py-1">
                <template x-for="(option, i) in visibleOptions" :key="option.value + '-' + option.index">
                    <li :id="optionId(option)"
                        role="option"
                        :aria-selected="option.value === value"
                        :aria-disabled="option.disabled"
                        :data-active="i === activeIndex"
                        class="mx-1 flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-sm transition-colors duration-100 {{ $centreOptions ? 'justify-center font-numeric' : '' }}"
                        :class="{
                            'bg-navy/[0.06] text-navy font-semibold': option.value === value,
                            'text-ink': option.value !== value && !option.disabled,
                            'text-faint cursor-not-allowed': option.disabled,
                            'bg-off': i === activeIndex && option.value !== value,
                            'ring-1 ring-inset ring-navy/25': i === activeIndex,
                        }"
                        @click="choose(option)"
                        @mousemove="activeIndex = i">

                        <span class="min-w-0 truncate {{ $centreOptions ? '' : 'flex-1' }}" x-text="option.label"></span>

                        {{-- Redundant next to a centred two-digit value; aria-selected still carries it --}}
                        <svg x-show="option.value === value" class="w-4 h-4 shrink-0 text-navy {{ $centreOptions ? 'hidden' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </li>
                </template>

                <template x-if="!visibleOptions.length">
                    <li class="px-3 py-6 text-center text-xs text-muted" role="presentation">
                        {{ __('messages.common.no_options') }}
                    </li>
                </template>
            </ul>
        </div>
    </template>

    @if ($error)
        <p id="{{ $descId }}" class="text-xs text-danger">{{ $error }}</p>
    @elseif ($success)
        <p id="{{ $descId }}" class="text-xs text-success">{{ $success }}</p>
    @elseif ($helper)
        <p id="{{ $descId }}" class="text-xs text-muted">{{ $helper }}</p>
    @endif
</div>
