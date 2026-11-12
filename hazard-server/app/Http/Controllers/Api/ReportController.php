<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\HazardResource;
use App\Jobs\ScreenReportImage;
use App\Models\Category;
use App\Models\Department;
use App\Models\HazardReport;
use App\Services\AdminReportAlerts;
use App\Services\PhotoStorage;
use App\Services\ReportUpdates;
use App\Services\SecurityEvents;
use App\Support\Location;
use App\Support\Workflow;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function catalog()
    {
        return ['categories' => Category::all(), 'departments' => Department::all()];
    }

    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(array_keys(Workflow::NEXT))], 'category' => 'nullable|exists:categories,id', 'search' => 'nullable|string|max:120', 'page' => 'nullable|integer|min:1']);
        $private = $request->routeIs('reports.mine', 'reports.admin');
        $query = HazardReport::with($private ? ['category', 'department', 'photos', 'history', 'reporter'] : ['category', 'department'])->withCount('confirmations');
        if ($request->routeIs('reports.mine')) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->routeIs('reports.admin')) {
            abort_unless($request->user()->role === 'admin', 403);
        } else {
            $query->communityVisible();
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['category'])) {
            $query->where('category_id', $data['category']);
        }
        if (! empty($data['search'])) {
            $query->where(function ($q) use ($data, $private) {
                $pattern = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $data['search']).'%';
                if ($private) {
                    foreach (['title', 'reference_number', 'barangay', 'address'] as $field) {
                        $q->orWhere($field, 'like', $pattern);
                    }
                } else {
                    $q->where('reference_number', 'like', $pattern)->orWhere('barangay', 'like', $pattern)
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $pattern))
                        ->orWhere(fn ($published) => $published->published()->where('public_title', 'like', $pattern));
                }
            });
        }

        return HazardResource::collection($query->latest()->paginate(100));
    }

    public function stats(Request $request)
    {
        $query = HazardReport::query();
        if ($request->routeIs('reports.admin.stats')) {
            abort_unless($request->user()->role === 'admin', 403);
        } else {
            $query->communityVisible();
        }
        $counts = $query->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['total' => $counts->sum() - ($counts['rejected'] ?? 0), 'active' => collect(['submitted', 'under_verification', 'verified', 'assigned', 'responding'])->sum(fn ($s) => $counts[$s] ?? 0), 'pending' => ($counts['submitted'] ?? 0) + ($counts['under_verification'] ?? 0), 'responding' => $counts['responding'] ?? 0, 'resolved' => ($counts['resolved'] ?? 0) + ($counts['closed'] ?? 0)];
    }

    public function nearby(Request $request)
    {
        $d = $request->validate(['category' => 'required|exists:categories,id', 'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180']);
        $matches = [];
        // The indexed category/time filter and latitude band keep candidate sets small on MySQL.
        $candidates = HazardReport::communityVisible()->where('category_id', $d['category'])->whereNotIn('status', ['resolved', 'closed', 'rejected'])->where('created_at', '>=', now()->subHours(6))->whereRaw('COALESCE(verified_latitude, selected_latitude) BETWEEN (? + 0.0) AND (? + 0.0)', [$d['latitude'] - 0.001, $d['latitude'] + 0.001])->cursor();
        foreach ($candidates as $r) {
            $distance = Location::distance($d['latitude'], $d['longitude'], $r->verified_latitude ?? $r->selected_latitude, $r->verified_longitude ?? $r->selected_longitude);
            if ($distance <= 100) {
                $matches[] = ['id' => $r->id, 'reference_number' => $r->reference_number, 'title' => $r->isPublished() ? $r->public_title : $r->category->name.' report', 'distance' => round($distance / 50) * 50, 'created_at' => $r->created_at];
            }
        }
        usort($matches, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return array_slice($matches, 0, 5);
    }

    public function store(StoreReportRequest $request, PhotoStorage $storage)
    {
        abort_unless($request->user()->role === 'citizen', 403);
        $data = $request->safe()->except(['photos', 'location_confirmed']);
        if (! empty($data['occurred_at'])) {
            $data['occurred_at'] = CarbonImmutable::parse($data['occurred_at'])->utc();
        }
        $photos = [];
        try {
            foreach ($request->file('photos') as $file) {
                $photos[] = $storage->store($file);
            }
            $report = DB::transaction(function () use ($request, $data, $photos) {
                $data['user_id'] = $request->user()->id;
                $data['department_id'] = Category::findOrFail($data['category_id'])->department_id;
                $data['reference_number'] = 'HZ-'.now()->year.'-'.strtoupper(Str::random(8));
                $data['location_difference_meters'] = isset($data['gps_latitude']) ? Location::distance($data['gps_latitude'], $data['gps_longitude'], $data['selected_latitude'], $data['selected_longitude']) : null;
                $report = HazardReport::create($data);
                $images = $report->photos()->createMany($photos);
                foreach ($images as $image) {
                    if ($image->ai_status === 'queued') {
                        ScreenReportImage::dispatch($image->id)->onConnection('image-screening')->onQueue('image-screening')->beforeCommit();
                    }
                }
                $report->history()->create(['actor_id' => $request->user()->id, 'status' => 'submitted', 'note' => 'Report submitted for municipal verification.']);
                $this->audit($report, $request, 'created', ['category_id' => $report->category_id]);
                app(AdminReportAlerts::class)->submitted($report);

                return $report;
            });
        } catch (\Throwable $error) {
            foreach ($photos as $photo) {
                try {
                    Storage::disk($photo['disk'])->delete($photo['path']);
                } catch (\Throwable) {
                }
            } throw $error;
        }

        return (new HazardResource($report->load(['category', 'department', 'photos'])->loadCount('confirmations')))->response()->setStatusCode(201);
    }

    public function show(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->id === $report->user_id, 403, 'Only the reporter and administrators can view private report details.');

        return new HazardResource($report->load(['category', 'department', 'photos', 'history', 'reporter'])->loadCount('confirmations'));
    }

    public function update(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Workflow::NEXT))], 'severity' => ['required', Rule::in(['low', 'moderate', 'high', 'critical'])], 'department_id' => 'required|exists:departments,id', 'assigned_responder' => 'nullable|string|min:2|max:100', 'note' => 'required|string|min:5|max:1000', 'verified_latitude' => 'nullable|required_with:verified_longitude|required_if:status,verified|numeric|between:-90,90', 'verified_longitude' => 'nullable|required_with:verified_latitude|required_if:status,verified|numeric|between:-180,180']);
        $request->validate(['public_title' => 'prohibited', 'public_summary' => 'prohibited', 'public_reporter_name' => 'prohibited', 'public_occurred_at' => 'prohibited']);
        $report = DB::transaction(function () use ($request, $report, $data) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            Workflow::check($report->status, $data['status']);
            if (in_array($data['status'], HazardReport::ACCEPTED_STATUSES, true) && ! $report->fact_checked_at) {
                throw ValidationException::withMessages(['status' => 'Use Fact-check incident to verify and accept this report first.']);
            }
            if ($data['status'] === 'rejected') {
                $data['public_title'] = null;
                $data['public_summary'] = null;
                $data['published_at'] = null;
                $data['published_by'] = null;
                $data['public_reporter_name'] = null;
                $data['public_occurred_at'] = null;
            }
            if ($data['status'] === 'assigned' && empty($data['assigned_responder']) && ! $report->assigned_responder) {
                throw ValidationException::withMessages(['assigned_responder' => 'Assign a responder or response team.']);
            }
            $note = $data['note'];
            unset($data['note']);
            $from = $report->status;
            foreach (['verified' => 'verified_at', 'responding' => 'responded_at', 'resolved' => 'resolved_at'] as $status => $field) {
                if ($data['status'] === $status && ! $report->$field) {
                    $data[$field] = now();
                }
            }
            $report->update($data);
            $report->history()->create(['actor_id' => $request->user()->id, 'status' => $report->status, 'note' => $note]);
            $this->audit($report, $request, 'updated', ['from' => $from, ...$data, 'note' => $note]);
            app(SecurityEvents::class)->record('municipal_response_changed', $request->user(), $request);
            app(ReportUpdates::class)->notify($report, ucfirst(str_replace('_', ' ', $report->status)).': '.$note);

            return $report;
        });

        return new HazardResource($report->load(['category', 'department', 'photos', 'history'])->loadCount('confirmations'));
    }

    public function confirm(Request $request, HazardReport $report)
    {
        return DB::transaction(function () use ($request, $report) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_unless($report->isCommunityVisible(), 404);
            abort_if(in_array($report->status, ['resolved', 'closed', 'rejected']), 422, 'This report is no longer active.');
            abort_if($request->user()->id === $report->user_id, 422, 'You already reported this hazard.');
            $report->confirmations()->firstOrCreate(['user_id' => $request->user()->id]);

            return ['confirmed' => true];
        });
    }

    public function destroy(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->id === $report->user_id, 403, 'Only your own unverified report can be deleted.');
        DB::transaction(function () use ($request, $report) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($report->status, ['submitted', 'under_verification'], true), 403, 'Only your own unverified report can be deleted.');
            $report->delete();
            $this->audit($report, $request, 'deleted', ['status' => $report->status]);
        });

        return response()->noContent();
    }

    private function audit(HazardReport $report, Request $request, string $action, array $data): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'hazard_report_id' => $report->id, 'action' => $action, 'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now()]);
    }
}
