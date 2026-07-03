<?php

namespace App\Livewire\Superadmin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Backups extends Component
{
    public function backupNow(): void
    {
        Artisan::call('backup:run');

        session()->flash('success', 'Backup started. Refresh in a moment to see it in the list.');
    }

    public function render()
    {
        $files = collect(Storage::disk('backups')->allFiles())
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(fn($f) => [
                'path'       => $f,
                'name'       => basename($f),
                'size'       => Storage::disk('backups')->size($f),
                'created_at' => Carbon::createFromTimestamp(Storage::disk('backups')->lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return view('livewire.superadmin.backups', ['files' => $files]);
    }
}
