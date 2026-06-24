<?php
// tests/Feature/Admin/NewsTest.php

use App\Livewire\Admin\News;
use App\Livewire\NewsFeed;
use App\Models\NewsPost;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->admin  = User::factory()->withRole('admin')->approved()->create();
    $this->parent = User::factory()->withRole('parent')->approved()->create();
    $this->coach  = User::factory()->withRole('coach')->approved()->create();
});

it('publishing a post notifies parents and coaches', function () {
    Livewire::actingAs($this->admin)->test(News::class)
        ->call('openCreate')
        ->set('title', 'Holiday schedule')
        ->set('body', 'Classes are off next week.')
        ->set('is_published', true)
        ->call('save')
        ->assertHasNoErrors();

    $post = NewsPost::first();
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();

    // parent + coach each get a bell notification; admin does not.
    expect(Notification::where('type', 'news')->count())->toBe(2);
});

it('saving a draft does not notify anyone', function () {
    Livewire::actingAs($this->admin)->test(News::class)
        ->call('openCreate')
        ->set('title', 'Draft')
        ->set('body', 'Not ready yet.')
        ->set('is_published', false)
        ->call('save');

    expect(NewsPost::first()->is_published)->toBeFalse();
    expect(Notification::where('type', 'news')->count())->toBe(0);
});

it('publishing a draft later sends the notification once', function () {
    $post = NewsPost::create(['title' => 'X', 'body' => 'Y', 'is_published' => false]);

    Livewire::actingAs($this->admin)->test(News::class)
        ->call('togglePublish', $post->id)
        ->call('togglePublish', $post->id)   // unpublish
        ->call('togglePublish', $post->id);  // publish again

    expect($post->fresh()->published_at)->not->toBeNull();
    // Notified only on the first publish (published_at already set afterwards).
    expect(Notification::where('type', 'news')->count())->toBe(2);
});

it('the feed shows published posts pinned-first and hides drafts', function () {
    NewsPost::create(['title' => 'Draft', 'body' => 'b', 'is_published' => false]);
    NewsPost::create(['title' => 'Older', 'body' => 'b', 'is_published' => true, 'published_at' => now()->subDay()]);
    NewsPost::create(['title' => 'Pinned', 'body' => 'b', 'is_published' => true, 'is_pinned' => true, 'published_at' => now()->subWeek()]);

    $posts = Livewire::actingAs($this->parent)->test(NewsFeed::class)->viewData('posts');

    expect($posts)->toHaveCount(2);                 // draft excluded
    expect($posts->first()->title)->toBe('Pinned'); // pinned first despite older date
});

it('parents and coaches can view the news page', function () {
    $this->actingAs($this->parent)->get(route('parent.news'))->assertOk();
    $this->actingAs($this->coach)->get(route('coach.news'))->assertOk();
});
