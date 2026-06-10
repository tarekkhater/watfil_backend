<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicFile
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (file_exists(storage_path('app/public/'.$path))) {
            return asset('storage/'.$path);
        }

        return asset($path);
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);

        $legacyPath = storage_path('app/public/'.$path);
        if (file_exists($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
