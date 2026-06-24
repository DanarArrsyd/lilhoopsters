<?php

namespace App\Livewire\Admin;

use App\Models\NewsPost;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class News extends Component
{
    use WithPagination, WithFileUploads;

    public string $search   = '';
    public bool $showModal  = false;
    public ?int $editingId  = null;

    public string $title    = '';
    public string $body     = '';
    public $image           = null;       // new upload
    public ?string $existingImage = null;
    public bool $is_pinned     = false;
    public bool $is_published   = true;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:5000',
            'image' => 'nullable|image|max:5120',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $post = NewsPost::findOrFail($id);
        $this->editingId     = $id;
        $this->title         = $post->title;
        $this->body          = $post->body;
        $this->existingImage = $post->image;
        $this->image         = null;
        $this->is_pinned     = $post->is_pinned;
        $this->is_published  = $post->is_published;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate();

        $post = $this->editingId ? NewsPost::findOrFail($this->editingId) : new NewsPost();

        $post->title       = $this->title;
        $post->body        = $this->body;
        $post->is_pinned   = $this->is_pinned;
        $post->is_published = $this->is_published;

        if ($this->image) {
            $post->image = $this->image->store('news', 'public');
        }

        if (! $this->editingId) {
            $post->author_id = auth()->id();
        }

        // First time it becomes published → stamp + broadcast.
        $shouldNotify = $this->is_published && $post->published_at === null;
        if ($shouldNotify) {
            $post->published_at = now();
        }

        $post->save();

        if ($shouldNotify) {
            $this->notifyAudience($post);
        }

        session()->flash('success', $this->editingId ? 'Post updated.' : 'Post created.');
        $this->showModal = false;
        $this->resetForm();
    }

    public function togglePin(int $id): void
    {
        $post = NewsPost::findOrFail($id);
        $post->update(['is_pinned' => ! $post->is_pinned]);
    }

    public function togglePublish(int $id): void
    {
        $post = NewsPost::findOrFail($id);
        $wasUnpublished = ! $post->is_published;

        $post->is_published = ! $post->is_published;
        if ($post->is_published && $post->published_at === null) {
            $post->published_at = now();
            $post->save();
            $this->notifyAudience($post);
            return;
        }
        $post->save();
    }

    public function confirmDelete(int $id): void
    {
        NewsPost::findOrFail($id)->delete();
        session()->flash('success', 'Post deleted.');
    }

    private function notifyAudience(NewsPost $post): void
    {
        $excerpt = Str::limit(strip_tags($post->body), 120);

        User::query()
            ->whereHas('role', fn($q) => $q->whereIn('name', ['parent', 'coach']))
            ->where('is_active', true)
            ->each(fn($u) => NotificationService::send(
                $u->id, 'news', $post->title, $excerpt, ['news_id' => $post->id]
            ));
    }

    public function resetForm(): void
    {
        $this->editingId     = null;
        $this->title         = '';
        $this->body          = '';
        $this->image         = null;
        $this->existingImage = null;
        $this->is_pinned     = false;
        $this->is_published  = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.news', [
            'posts' => NewsPost::query()
                ->with('author')
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }
}
