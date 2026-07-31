<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.attendance.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.pages.attendance.subtitle') }}</p>
    </div>

    {{-- Child filter tabs --}}
    @if ($children->count() > 1)
        <div class="flex gap-2 flex-wrap mb-4">
            @foreach ($children as $child)
                <button
                    wire:click="selectChild({{ $child->id }})"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-white border-navy'                                         => $filterChildId == $child->id,
                        'bg-white text-muted border-line hover:border-navy/40 hover:text-ink'    => $filterChildId != $child->id,
                    ])>
                    {{ $child->name }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Enrollment / program tabs --}}
    @if ($enrollments->count() > 1)
        <div class="flex gap-2 flex-wrap mb-5">
            @foreach ($enrollments as $enr)
                <button
                    wire:click="selectEnrollment({{ $enr->id }})"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-white border-navy'                                         => $activeEnrollment?->id == $enr->id,
                        'bg-white text-muted border-line hover:border-navy/40 hover:text-ink'    => $activeEnrollment?->id != $enr->id,
                    ])>
                    {{ $enr->schedule->program->name }}
                </button>
            @endforeach
        </div>
    @endif

    @if (!$activeEnrollment)
        <x-empty-state :title="__('messages.attendance.no_programs_title')" :description="__('messages.attendance.no_programs_desc')" />
    @else
        {{-- Enrollment info bar --}}
        <div class="flex items-center gap-3 mb-4 px-1">
            <div class="min-w-0">
                <p class="text-sm font-bold text-ink">
                    {{ $activeEnrollment->schedule->program->name }}
                    <span class="text-faint font-normal">·</span>
                    {{ $activeEnrollment->schedule->location->name }}
                </p>
                <p class="text-xs text-faint">
                    {{ ucfirst($activeEnrollment->schedule->day_of_week) }}s,
                    {{ \Carbon\Carbon::parse($activeEnrollment->schedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($activeEnrollment->schedule->end_time)->format('H:i') }}
                    @if ($activeEnrollment->started_at && $activeEnrollment->expires_at)
                        · {{ $activeEnrollment->started_at->format('d M Y') }} – {{ $activeEnrollment->expires_at->format('d M Y') }}
                    @endif
                </p>
            </div>
            @if ($activeEnrollment->total_sessions)
                <div class="ml-auto shrink-0 text-right">
                    <p class="text-xs font-bold text-navy">{{ __('messages.attendance.sessions_count', ['n' => $sessions->count()]) }}</p>
                    @php
                        $attended = $sessions->filter(fn($s) => $s['attendance'] && $s['attendance']->status === 'present')->count();
                        $pastCount = $sessions->filter(fn($s) => $s['is_past'] || $s['is_today'])->count();
                    @endphp
                    @if ($pastCount > 0)
                        <p class="text-3xs text-faint">{{ __('messages.attendance.attended', ['a' => $attended, 'b' => $pastCount]) }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Session list --}}
        <x-card padding="p-0">
            @forelse ($sessions as $session)
                <div @class([
                    'px-4 py-3.5 border-b border-line last:border-b-0',
                    'bg-navy/[0.03]' => $session['is_today'],
                ])>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs font-bold bg-off text-ink px-2.5 py-1 rounded-lg shrink-0">
                                {{ __('messages.attendance.session', ['n' => $session['number']]) }}
                            </span>
                            <span @class([
                                'text-xs',
                                'text-navy font-semibold' => $session['is_today'],
                                'text-faint'              => !$session['is_today'] && !$session['is_past'],
                                'text-muted'              => $session['is_past'],
                            ])>
                                {{ $session['is_today'] ? __('messages.attendance.today') : ($session['is_past'] ? __('messages.attendance.finished') : __('messages.attendance.scheduled')) }}
                            </span>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg shrink-0 {{ $session['badge']['class'] }}">
                            {{ $session['badge']['label'] }}
                        </span>
                    </div>
                    <p class="text-sm text-ink mt-1.5">
                        {{ $session['date']->format('l, d F Y') }}
                        <span class="text-faint">{{ \Carbon\Carbon::parse($activeEnrollment->schedule->start_time)->format('H:i') }}</span>
                    </p>
                    @if ($session['attendance']?->notes)
                        <p class="text-xs text-faint mt-0.5 italic">{{ $session['attendance']->notes }}</p>
                    @endif
                </div>
            @empty
                <x-empty-state :title="__('messages.attendance.no_sessions_title')" :description="__('messages.attendance.no_sessions_desc')" />
            @endforelse
        </x-card>
    @endif
</div>
