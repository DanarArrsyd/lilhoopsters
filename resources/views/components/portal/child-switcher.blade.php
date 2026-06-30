@props(['children', 'activeChildId'])

@if ($children->count() > 1)
    <div class="flex gap-2 overflow-x-auto pb-1 mb-5">
        @foreach ($children as $c)
            <button wire:click="switchChild({{ $c->id }})"
                    @class([
                        'shrink-0 text-sm font-semibold px-4 py-1.5 rounded-full border transition-colors',
                        'bg-navy text-off border-navy' => $c->id === $activeChildId,
                        'bg-surface text-ink border-line hover:border-navy/40' => $c->id !== $activeChildId,
                    ])>
                {{ $c->name }}
            </button>
        @endforeach
    </div>
@endif
