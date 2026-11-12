<?php

namespace App\Services;

use App\Support\SecurityLock;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadProtection
{
    public function inspect(string $bytes): void
    {
        if (! config('security.scan_uploads')) {
            return;
        }
        $result = app(UploadScanner::class)->scan($bytes);
        if ($result === 'clean') {
            return;
        }
        app(SecurityEvents::class)->record($result === 'infected' ? 'upload_rejected_malware' : 'upload_scanner_unavailable');
        if ($result === 'infected') {
            // Best-effort private samples, bounded to 100 files total under a
            // shared lock. No report/media row is created for a rejected upload.
            $lock = SecurityLock::make('security-quarantine', 10);
            if ($lock->get()) {
                try {
                    $disk = Storage::disk('quarantine');
                    $prefix = now()->format('Y-m-d');
                    if (count($disk->allFiles()) < 100) {
                        $disk->put($prefix.'/'.Str::uuid().'.bin', $bytes, 'private');
                    }
                } finally {
                    $lock->release();
                }
            }
            throw ValidationException::withMessages(['photos' => 'A photo failed the security check. Remove it and choose another photo.']);
        }
        abort(503, 'Photo security scanning is temporarily unavailable. Your upload was not saved. Try again later.');
    }
}
