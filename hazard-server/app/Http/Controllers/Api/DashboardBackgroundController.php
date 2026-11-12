<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhotoStorage;
use App\Services\SecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardBackgroundController extends Controller
{
    public function show(string $id)
    {
        $current = json_decode(DB::table('municipal_settings')->where('key', 'dashboard_background')->value('value') ?? 'null', true);
        abort_unless($current && $current['id'] === $id && Storage::disk('branding')->exists($current['path']), 404);

        return response(Storage::disk('branding')->get($current['path']))->header('Content-Type', 'image/webp')->header('X-Content-Type-Options', 'nosniff');
    }

    public function store(Request $request, PhotoStorage $photos)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $request->validate(['image' => 'required|file|mimes:jpeg,png,webp|max:1024|dimensions:max_width=1920,max_height=1920']);
        $bytes = $photos->prepare($request->file('image'));
        $id = (string) Str::uuid();
        $path = $id.'.webp';
        abort_unless(Storage::disk('branding')->put($path, $bytes, 'private'), 503, 'Background storage is unavailable.');
        try {
            $this->replace($request, ['id' => $id, 'path' => $path]);
        } catch (\Throwable $error) {
            Storage::disk('branding')->delete($path);
            throw $error;
        }

        return ['dashboard_background' => '/api/dashboard-background/'.$id];
    }

    public function destroy(Request $request): array
    {
        abort_unless($request->user()->role === 'admin', 403);
        $this->replace($request, null);

        return ['dashboard_background' => null];
    }

    private function replace(Request $request, ?array $next): void
    {
        $old = DB::transaction(function () use ($request, $next) {
            $row = DB::table('municipal_settings')->where('key', 'dashboard_background')->lockForUpdate()->firstOrFail();
            DB::table('municipal_settings')->where('key', 'dashboard_background')->update(['value' => json_encode($next), 'updated_at' => now()]);
            app(SecurityEvents::class)->record($next ? 'dashboard_background_changed' : 'dashboard_background_removed', $request->user(), $request);

            return json_decode($row->value, true);
        });
        if ($old) {
            Storage::disk('branding')->delete($old['path']);
        }
    }
}
