<?php

namespace App\Jobs;

use App\Models\HazardPhoto;
use App\Models\HazardReport;
use App\Services\LocalImageDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ScreenReportImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 15;

    public function __construct(public string $photoId) {}

    public function handle(LocalImageDetector $detector): void
    {
        $photo = HazardPhoto::find($this->photoId);
        if (! $photo || $photo->ai_status !== 'queued' || ! HazardReport::whereKey($photo->hazard_report_id)->exists()) {
            return;
        }
        try {
            if (Storage::disk($photo->disk)->size($photo->path) > 1024 * 1024) {
                throw new \RuntimeException;
            }
            $bytes = Storage::disk($photo->disk)->get($photo->path);
            $result = $detector->assess($bytes);
        } catch (\Throwable) {
            $result = ['ai_status' => 'unavailable', 'ai_probability' => null, 'ai_model' => null];
        }
        DB::transaction(function () use ($photo, $result) {
            // Same lock order as manual review and factual acceptance.
            $report = HazardReport::whereKey($photo->hazard_report_id)->lockForUpdate()->first();
            $current = HazardPhoto::whereKey($photo->id)->lockForUpdate()->first();
            if (! $report || ! $current || $current->ai_status !== 'queued') {
                return;
            }
            $current->forceFill([...$result, 'ai_assessed_at' => now(), 'assessment_version' => $current->assessment_version + 1])->save();
        });
    }
}
