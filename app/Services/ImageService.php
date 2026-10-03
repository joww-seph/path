<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resizes uploaded photos into WebP files on the public disk, using PHP's GD extension.
 */
class ImageService
{
    public const LARGE_WIDTH = 1600;

    public const THUMBNAIL_WIDTH = 480;

    /**
     * Store a large and a thumbnail WebP copy of an uploaded image.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function storeResized(UploadedFile $file, string $directory): array
    {
        $source = $this->load($file);
        $name = Str::uuid()->toString();

        try {
            return [
                'path' => $this->save($this->scale($source, self::LARGE_WIDTH), "{$directory}/{$name}.webp"),
                'thumbnail_path' => $this->save($this->scale($source, self::THUMBNAIL_WIDTH), "{$directory}/{$name}-thumb.webp"),
            ];
        } finally {
            imagedestroy($source);
        }
    }

    public function delete(string ...$paths): void
    {
        Storage::disk('public')->delete(array_filter($paths, fn (string $path) => ! str_starts_with($path, '/')));
    }

    private function load(UploadedFile $file): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The uploaded file is not an image GD can read.');
        }

        return $this->applyExifOrientation($image, $file);
    }

    /**
     * Phone cameras store photos sideways and record the rotation in EXIF; turn them upright.
     */
    private function applyExifOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        $angle = match ((int) $orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated;
    }

    private function scale(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);

        return $width > $maxWidth ? imagescale($image, $maxWidth) : $image;
    }

    private function save(GdImage $image, string $path): string
    {
        ob_start();
        imagewebp($image, null, 80);
        Storage::disk('public')->put($path, (string) ob_get_clean());

        return $path;
    }
}
