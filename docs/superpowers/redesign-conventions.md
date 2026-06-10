# Redesign Conventions (playbook for internal content pages)

Apply these when redesigning any Admin/Coach/Superadmin page. Presentation-only: never change Livewire PHP, route names, `wire:model`/`wire:click`/`wire:loading` bindings, `@error`, `@csrf`, model method calls, or pagination. All copy in English. Verify with `npm run build` + `php artisan test` (244 green).

## Tokens (Tailwind utilities)
- Surfaces: `bg-off` (page), `bg-surface` (cards/inputs), `border-line`.
- Brand/ink: `text-navy` (headings, primary), `text-ink` (body), `text-muted` (secondary), `text-faint` (tertiary/placeholder).
- Status (small doses only — chips/dots): success `#15803D`, warning `#B45309`, danger `#B91C1C`, info `#1D4ED8`. Use as `bg-[#15803D]/10 text-[#15803D]` etc. Destructive solid button uses `#DC2626`.
- NO orange/slate/amber/green-100/blue-100/pink/purple/teal/`bg-white`. Two-tone only + status.

## Page skeleton
```blade
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Page Title</h2>
        <p class="text-sm text-muted">One-line description.</p>
    </div>
    {{-- optional: <div class="...">{{ actions / "New" button }}</div> --}}
    {{-- filters card, then content card --}}
</div>
```
If the page has a primary action (e.g. "New Location"), put a header row: `<div class="flex items-start justify-between gap-4 mb-6">` with the title block on the left and `<x-btn>` on the right.

## Components (reuse — do not hand-roll)
- `<x-card>` (`title`, `padding`, `action` slot) — white surface, rounded-2xl, border-line.
- `<x-input>` — label above, navy focus ring.
- `<x-select>` — styled select with custom chevron; put `<option>`s in slot.
- `<x-btn>` — `variant` primary (navy) / secondary (navy outline) / ghost / danger; uppercase.
- `<x-badge :status="...">` — maps statuses to status colors automatically.
- `<x-empty-state title=... description=...>` — for empty lists.
- `<x-alert type="success|error|warning|info">` — flash messages.

## Filters row
```blade
<x-card class="mb-4" padding="p-4">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1"><x-input wire:model.live.debounce.300ms="search" placeholder="Search..." /></div>
        <div class="w-full sm:w-48"><x-select wire:model.live="filterX"><option value="">All</option>...</x-select></div>
    </div>
</x-card>
```

## Tables (admin/coach productivity tables)
- Wrap in `<x-card padding="p-0">` → `<div class="overflow-x-auto"><table class="w-full text-sm min-w-[640px]">`.
- `<thead>`: `<tr class="border-b border-line">`, `<th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">`.
- `<tbody class="divide-y divide-line">`, rows `hover:bg-off transition-colors`, cells `py-3 px-4`.
- Person avatar: `w-8 h-8 rounded-full bg-navy/8 text-navy flex items-center justify-center text-sm font-bold` with initial.
- Numbers/emphasis: `text-ink font-semibold`; secondary: `text-muted`.
- Empty: `@empty <tr><td colspan="N" class="py-2"><x-empty-state .../></td></tr>`.
- Pagination: `@if($items->hasPages()) <div class="px-4 py-3 border-t border-line">{{ $items->links() }}</div> @endif`.
- Tables → cards (full stacking) ONLY for the consumer Parent portal; admin/coach use horizontal scroll.

## Modals (CRUD)
Keep the existing Livewire open/close mechanism (`wire:click`, `$showModal`, `wire:model`, etc.) — only restyle. Standard pattern:
```blade
@if ($showModal)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
    <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editing ? 'Edit' : 'New' }} Item</h3>
            <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1">✕</button>
        </div>
        <form wire:submit="save" class="p-6 space-y-4">
            {{-- x-input / x-select fields with :error --}}
            <div class="flex gap-3 pt-2">
                <x-btn type="button" variant="secondary" wire:click="$set('showModal', false)" class="flex-1">Cancel</x-btn>
                <x-btn type="submit" class="flex-1">Save</x-btn>
            </div>
        </form>
    </div>
</div>
@endif
```
Match the ACTUAL property/method names found in the component (e.g. it may be `closeModal()` not `$set`). Read the component first.

## Toggles / inline actions
- Inline text actions (e.g. "Revoke", "Approve"): use `<x-btn variant="ghost" size="sm">` or a small `<button class="text-xs font-bold uppercase text-navy hover:underline">`; destructive → `text-[#B91C1C]`.
- Status pills: `<x-badge :status=...>`.

## Process
Read the component (`app/Livewire/.../X.php`) for property/method/binding names BEFORE rewriting its view. Preserve every binding. Reference implementations: `resources/views/livewire/admin/dashboard.blade.php` and `resources/views/livewire/admin/players.blade.php`.
