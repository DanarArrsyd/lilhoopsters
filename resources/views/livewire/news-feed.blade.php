<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">News</h2>
        <p class="text-sm text-muted">Announcements and updates from Lil' Hoopsters.</p>
    </div>

    @forelse ($posts as $post)
        <article class="bg-surface border border-line rounded-2xl overflow-hidden">
            @if ($post->image)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($post->image) }}" alt="{{ $post->title }}"
                     class="w-full max-h-72 object-cover">
            @endif
            <div class="p-5">
                <div class="flex items-center gap-2 mb-1.5">
                    @if ($post->is_pinned)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-[#B45309] bg-[#B45309]/10 rounded-md px-1.5 py-0.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M16 9V4h1a1 1 0 100-2H7a1 1 0 000 2h1v5a3 3 0 01-3 3v2h5.97v7l1 1 1-1v-7H19v-2a3 3 0 01-3-3z"/></svg>
                            Pinned
                        </span>
                    @endif
                    <span class="text-[11px] text-faint">{{ $post->published_at?->format('d M Y') }}</span>
                </div>
                <h3 class="text-lg font-extrabold text-navy leading-tight">{{ $post->title }}</h3>
                <div class="text-sm text-ink mt-2 whitespace-pre-line leading-relaxed">{{ $post->body }}</div>
                @if ($post->author)
                    <p class="text-[11px] text-faint mt-3">Posted by {{ $post->author->name }}</p>
                @endif
            </div>
        </article>
    @empty
        <x-empty-state title="No news yet" description="Check back later for announcements." />
    @endforelse

    @if ($posts->hasPages())
        <div>{{ $posts->links() }}</div>
    @endif
</div>
