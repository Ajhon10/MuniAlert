<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhotoStorage
{
    public function store(UploadedFile $file): array
    {
        $compressed = $this->prepare($file);
        $disk = config('hazards.photo_disk');
        if (! in_array($disk, ['evidence', 'r2'], true)) {
            throw new \RuntimeException('Private evidence storage must use the evidence disk or a private R2 bucket.');
        }
        $path = 'reports/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        if (! Storage::disk($disk)->put($path, $compressed, ['ContentType' => 'image/webp'])) {
            throw new \RuntimeException('Photo storage is unavailable.');
        }

        return ['authenticity_review_required' => true, 'ai_status' => app(LocalImageDetector::class)->configured() ? 'queued' : 'not_assessed', 'disk' => $disk, 'path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200), 'sha256' => hash_file('sha256', $file->getRealPath()), 'bytes' => strlen($compressed)];
    }

    public function prepare(UploadedFile $file): string
    {
        $bytes = file_get_contents($file->getRealPath());
        if (! is_string($bytes) || strlen($bytes) > 1024 * 1024) {
            throw ValidationException::withMessages(['photos' => 'Each photo must be at most 1 MB.']);
        }
        app(UploadProtection::class)->inspect($bytes);
        $info = @getimagesizefromstring($bytes);
        if (! $info || $info[0] > 1920 || $info[1] > 1920) {
            throw ValidationException::withMessages(['photos' => 'Images must be at most 1920 pixels per side.']);
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw ValidationException::withMessages(['photos' => 'The image cannot be decoded.']);
        }
        try {
            for ($quality = 82; $quality >= 30; $quality -= 13) {
                ob_start();
                imagewebp($image, null, $quality);
                $compressed = ob_get_clean();
                if (strlen($compressed) <= 1024 * 1024) {
                    break;
                }
            }
        } finally {
            imagedestroy($image);
        }
        if (! $compressed || strlen($compressed) > 1024 * 1024) {
            throw ValidationException::withMessages(['photos' => 'The compressed image must be under 1 MB.']);
        }
        app(UploadProtection::class)->inspect($compressed);

        return $compressed;
    }
}
