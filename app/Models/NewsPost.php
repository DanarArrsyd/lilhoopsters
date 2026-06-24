<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'body', 'image', 'is_published', 'is_pinned',
        'published_at', 'author_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned'    => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published posts, pinned first then newest. */
    public function scopePublishedFeed(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');
    }
}
