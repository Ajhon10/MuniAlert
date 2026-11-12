<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HazardResource;
use App\Models\HazardReport;
use App\Services\SecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportPublicationController extends Controller
{
    public function store(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['public_title' => 'required|string|min:5|max:120', 'public_summary' => 'required|string|min:15|max:1000', 'privacy_reviewed' => 'required|accepted', 'include_reporter_name' => 'sometimes|boolean']);
        unset($data['privacy_reviewed']);
        $report = DB::transaction(function () use ($request, $report, $data) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_unless($report->isCommunityVisible(), 422, 'Fact-check and accept this report before creating a news post.');
            $includeReporter = $data['include_reporter_name'] ?? $report->public_reporter_name !== null;
            unset($data['include_reporter_name']);
            $report->forceFill([...$data, 'public_reporter_name' => $includeReporter ? $report->reporter->name : null,
                'public_occurred_at' => $report->occurred_at,
                'published_at' => $report->published_at ?? now(), 'published_by' => $request->user()->id])->save();
            $this->audit($request, $report, 'news_published');

            return $report;
        });

        return $this->resource($report);
    }

    public function destroy(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $report = DB::transaction(function () use ($request, $report) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $report->forceFill(['public_title' => null, 'public_summary' => null, 'published_at' => null, 'published_by' => null, 'public_reporter_name' => null, 'public_occurred_at' => null])->save();
            $this->audit($request, $report, 'news_unpublished');

            return $report;
        });

        return $this->resource($report);
    }

    private function resource(HazardReport $report): HazardResource
    {
        return new HazardResource($report->load(['category', 'department', 'photos', 'history', 'reporter'])->loadCount('confirmations'));
    }

    private function audit(Request $request, HazardReport $report, string $action): void
    {
        DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'hazard_report_id' => $report->id, 'action' => $action, 'data' => json_encode(['published' => $report->published_at !== null]), 'created_at' => now(), 'updated_at' => now()]);
        app(SecurityEvents::class)->record($action, $request->user(), $request);
    }
}
