<div class="inline-flex items-center bg-off border border-line rounded-lg p-0.5 text-2xs font-bold">
    <button wire:click="switchTo('en')"
            class="px-2 py-1 rounded-md transition-colors {{ $locale === 'en' ? 'bg-navy text-off' : 'text-muted hover:text-ink' }}">EN</button>
    <button wire:click="switchTo('id')"
            class="px-2 py-1 rounded-md transition-colors {{ $locale === 'id' ? 'bg-navy text-off' : 'text-muted hover:text-ink' }}">ID</button>
</div>
