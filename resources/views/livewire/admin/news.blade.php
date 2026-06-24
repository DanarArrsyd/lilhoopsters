<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">News</h2>
        <p class="text-sm text-muted">Post announcements — published posts notify all parents and coaches.</p>
    </div>

    <button wire:click="openCreate"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

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
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Title</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Date</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-off transition-colors" wire:key="post-{{ $post->id }}">
                            <td class="py-3 px-4">
                                <span class="font-semibold text-ink">{{ $post->title }}</span>
                                @if ($post->is_pinned)
                                    <span class="ml-1 text-[10px] font-bold uppercase text-[#B45309]">· Pinned</span>
                                @endif
                                <span class="block text-xs text-faint max-w-md truncate">{{ \Illuminate\Support\Str::limit(strip_tags($post->body), 80) }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex text-[11px] font-bold px-2 py-0.5 rounded-md {{ $post->is_published ? 'bg-[#15803D]/10 text-[#15803D]' : 'bg-line text-faint' }}">
                                    {{ $post->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm" wire:click="openEdit({{ $post->id }})" wire:loading.attr="disabled">Edit</x-btn>
                                    <x-btn variant="ghost" size="sm" wire:click="togglePin({{ $post->id }})" wire:loading.attr="disabled" wire:target="togglePin({{ $post->id }})">
                                        {{ $post->is_pinned ? 'Unpin' : 'Pin' }}
                                    </x-btn>
                                    <x-btn variant="{{ $post->is_published ? 'warning' : 'success' }}" size="sm"
                                           wire:click="togglePublish({{ $post->id }})" wire:loading.attr="disabled" wire:target="togglePublish({{ $post->id }})">
                                        {{ $post->is_published ? 'Unpublish' : 'Publish' }}
                                    </x-btn>
                                    <x-btn variant="danger" size="sm" wire:click="confirmDelete({{ $post->id }})"
                                           wire:confirm="Delete this post?" wire:loading.attr="disabled" wire:target="confirmDelete({{ $post->id }})">Delete</x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2"><x-empty-state title="No posts yet" description="Create your first announcement." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($posts->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $posts->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Post' : 'New Post' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="title" label="Title" placeholder="e.g. Holiday schedule update" required :error="$errors->first('title')" />

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Body</label>
                        <textarea wire:model="body" rows="5" aria-label="Body"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="Write your announcement..."></textarea>
                        @error('body') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Image (optional)</label>
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
                            <input type="checkbox" wire:model="is_published" class="rounded accent-navy"> Published
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                            <input type="checkbox" wire:model="is_pinned" class="rounded accent-navy"> Pinned
                        </label>
                    </div>
                    <p class="text-[11px] text-faint">Publishing notifies all parents and coaches in their notification bell.</p>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">Cancel</x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled" wire:target="save,image">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Save' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
