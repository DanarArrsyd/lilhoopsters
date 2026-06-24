<?php

namespace App\Livewire;

use App\Models\NewsPost;
use Livewire\Component;
use Livewire\WithPagination;

class NewsFeed extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.news-feed', [
            'posts' => NewsPost::query()->publishedFeed()->with('author')->paginate(8),
        ]);
    }
}
