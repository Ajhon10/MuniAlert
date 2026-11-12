<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScreenReportImage;
use App\Models\HazardReport;
use App\Services\PhotoStorage;
use App\Services\SecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EvidenceController extends Controller
{
    public function show(Request $request, HazardReport $report, string $photo)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->id === $report->user_id, 403);
        $evidence = $report->photos()->findOrFail($photo);

        return Storage::disk($evidence->disk)->response($evidence->path, 'evidence.webp', [
            'Content-Type' => 'image/webp', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
        ], 'inline');
    }

    public function store(Request $request, HazardReport $report, PhotoStorage $storage)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $request->validate(['photos' => 'required|array|min:1|max:3', 'photos.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:1024|dimensions:max_width=1920,max_height=1920']);
        $photos = [];
        try {
            foreach ($request->file('photos') as $file) {
                $photos[] = [...$storage->store($file), 'photo_type' => 'resolution'];
            }
            DB::transaction(function () use ($request, $report, $photos) {
                $locked = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
                if (! in_array($locked->status, ['assigned', 'responding', 'resolved'], true)) {
                    throw ValidationException::withMessages(['photos' => 'Resolution evidence can be added to assigned, responding or resolved reports.']);
                }
                if ($locked->photos()->where('photo_type', 'resolution')->count() + count($photos) > 3) {
                    throw ValidationException::withMessages(['photos' => 'A report may have up to three resolution photos.']);
                }
                $images = $locked->photos()->createMany($photos);
                foreach ($images as $image) {
                    if ($image->ai_status === 'queued') {
                        ScreenReportImage::dispatch($image->id)->onConnection('image-screening')->onQueue('image-screening')->beforeCommit();
                    }
                }
                app(SecurityEvents::class)->record('resolution_evidence_added', $request->user(), $request);
                DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'hazard_report_id' => $locked->id, 'action' => 'resolution_photos_added', 'data' => json_encode(['count' => count($photos)]), 'created_at' => now(), 'updated_at' => now()]);
            });
        } catch (\Throwable $error) {
            foreach ($photos as $photo) {
                try {
                    Storage::disk($photo['disk'])->delete($photo['path']);
                } catch (\Throwable) {
                }
            }
            throw $error;
        }

        return response()->json(['message' => 'Resolution photos saved privately.'], 201);
    }
}
