<?php

declare(strict_types=1);

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Uploaded product photos otherwise get stored raw — whatever resolution and
 * orientation the staff member's phone produced, which is why they display
 * inconsistently across the app. This normalizes a photo once, at upload
 * time, so every later render just needs `object-cover` on a fixed box.
 *
 * Builds its own `ImageManager` rather than using Intervention's `Image`
 * facade — Laravel's own framework also ships a container binding named
 * `image` (`Illuminate\Image\ImageManager`, used by `UploadedFile::fake()`),
 * which collides with Intervention's facade of the same binding name.
 */
final class ProductImageProcessor
{
    private const MAX_LONG_EDGE = 1600;

    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $image = ImageManager::gd()->read($file->getRealPath());

        $image->scaleDown(width: self::MAX_LONG_EDGE, height: self::MAX_LONG_EDGE);

        $path = $directory.'/'.Str::uuid()->toString().'.jpg';

        Storage::disk($disk)->put($path, (string) $image->toJpeg(quality: 85));

        return $path;
    }
}
