<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupDownloadController extends Controller
{
    public function __invoke(Request $request, string $filename)
    {
        // Guard against path traversal: only allow the exact relative path
        // as stored, and refuse to serve outside the backups disk.
        if (str_contains($filename, '..') || !Storage::disk('backups')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('backups')->download($filename);
    }
}
