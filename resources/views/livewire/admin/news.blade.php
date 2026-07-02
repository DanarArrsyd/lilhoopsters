<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.news.title')" :subtitle="__('messages.admin.news.subtitle')">
        <x-slot name="action">
            <x-btn variant="add" wire:click="openCreate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('messages.admin.news.new') }}
            </x-btn>
        </x-slot>
    </x-admin.page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search posts..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.news.col_title') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.news.col_status') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.news.col_date') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-off transition-colors" wire:key="post-{{ $post->id }}">
                            <td class="py-3 px-4">
                                <span class="font-semibold text-ink">{{ $post->title }}</span>
                                @if ($post->is_pinned)
                                    <span class="ml-1 text-[10px] font-bold uppercase text-[#B45309]">· {{ __('messages.admin.news.pinned_badge') }}</span>
                                @endif
                                <span class="block text-xs text-faint max-w-md truncate">{{ \Illuminate\Support\Str::limit(strip_tags($post->body), 80) }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex text-[11px] font-bold px-2 py-0.5 rounded-md {{ $post->is_published ? 'bg-[#15803D]/10 text-[#15803D]' : 'bg-line text-faint' }}">
                                    {{ $post->is_published ? __('messages.admin.news.published_badge') : __('messages.admin.news.draft_badge') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm" wire:click="openEdit({{ $post->id }})" wire:loading.attr="disabled">{{ __('messages.common.edit') }}</x-btn>
                                    <x-btn variant="ghost" size="sm" wire:click="togglePin({{ $post->id }})" wire:loading.attr="disabled" wire:target="togglePin({{ $post->id }})">
                                        {{ $post->is_pinned ? __('messages.common.unpin') : __('messages.common.pin') }}
                                    </x-btn>
                                    <x-btn variant="{{ $post->is_published ? 'warning' : 'success' }}" size="sm"
                                           wire:click="togglePublish({{ $post->id }})" wire:loading.attr="disabled" wire:target="togglePublish({{ $post->id }})">
                                        {{ $post->is_published ? __('messages.common.unpublish') : __('messages.common.publish') }}
                                    </x-btn>
                                    <x-btn variant="danger" size="sm" wire:click="confirmDelete({{ $post->id }})"
                                           wire:confirm="Delete this post?" wire:loading.attr="disabled" wire:target="confirmDelete({{ $post->id }})">{{ __('messages.common.delete') }}</x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2"><x-empty-state :title="__('messages.admin.news.empty_title')" :description="__('messages.admin.news.empty_desc')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($posts->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $posts->links() }}</div>
        @endif
    </x-card>

    </div>{{-- /max-w-6xl --}}

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? __('messages.admin.news.modal_edit') : __('messages.admin.news.modal_new') }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="title" label="{{ __('messages.admin.news.label_title') }}" placeholder="e.g. Holiday schedule update" required :error="$errors->first('title')" />

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.news.label_body') }}</label>
                        <textarea wire:model="body" rows="5" aria-label="Body"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="{{ __('messages.admin.news.body_ph') }}"></textarea>
                        @error('body') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.news.label_image') }}</label>
                        <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-navy file:text-off file:px-3 file:py-1.5 file:text-xs file:font-semibold">
                        @error('image') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="image" class="text-[11px] text-muted">Uploading…</div>
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="mt-2 rounded-lg max-h-40 object-cover">
                        @elseif ($existingImage)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImage) }}" class="mt-2 rounded-lg max-h-40 object-cover">
                        @endif
                    </div>

                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                            <input type="checkbox" wire:model="is_published" class="rounded accent-navy"> {{ __('messages.admin.news.label_published') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                            <input type="checkbox" wire:model="is_pinned" class="rounded accent-navy"> {{ __('messages.admin.news.label_pinned') }}
                        </label>
                    </div>
                    <p class="text-[11px] text-faint">{{ __('messages.admin.news.publish_notice') }}</p>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">{{ __('messages.common.cancel') }}</x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled" wire:target="save,image">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.common.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
