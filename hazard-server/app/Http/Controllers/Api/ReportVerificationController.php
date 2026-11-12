<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HazardResource;
use App\Models\HazardReport;
use App\Services\ReportUpdates;
use App\Services\SecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportVerificationController extends Controller
{
    public function store(Request $request, HazardReport $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'confirmed_happened' => 'required|accepted',
            'method' => ['required', Rule::in(['site_visit', 'responder_confirmation', 'official_record', 'cross_checked_evidence'])],
            'verification_note' => 'required|string|min:15|max:1000',
            'verified_latitude' => 'required|numeric|between:-90,90',
            'verified_longitude' => 'required|numeric|between:-180,180',
        ]);
        $report = DB::transaction(function () use ($request, $report, $data) {
            $report = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_if($report->status === 'rejected', 422, 'A rejected report cannot be accepted through this action.');
            abort_unless(in_array($report->status, ['submitted', 'under_verification', ...HazardReport::ACCEPTED_STATUSES], true), 422, 'This report is not in a reviewable state.');
            abort_if($report->fact_checked_at !== null, 422, 'This incident already has a recorded fact-check.');
            $pendingImages = $report->photos()->where('photo_type', 'incident')->where('authenticity_review_required', true)
                ->where(fn ($images) => $images->whereNull('reviewed_at')->orWhereNull('reviewed_assessment_version')->orWhere('review_verdict', '!=', 'accepted_evidence')->orWhereColumn('reviewed_assessment_version', '!=', 'assessment_version'))->exists();
            abort_if($pendingImages, 422, 'Review each incident photo and accept its evidence before completing the factual verification. Contact responders first if immediate assistance is needed.');
            if ($report->status === 'submitted') {
                $report->history()->create(['actor_id' => $request->user()->id, 'status' => 'under_verification', 'note' => 'Municipal review completed before acceptance.']);
            }
            $report->forceFill([
                'status' => in_array($report->status, ['submitted', 'under_verification'], true) ? 'verified' : $report->status,
                'fact_checked_at' => now(), 'fact_checked_by' => $request->user()->id,
                'fact_check_method' => $data['method'], 'fact_check_note' => $data['verification_note'],
                'verified_latitude' => $data['verified_latitude'], 'verified_longitude' => $data['verified_longitude'],
                'verified_at' => $report->verified_at ?? now(),
            ])->save();
            $report->history()->create(['actor_id' => $request->user()->id, 'status' => $report->status, 'note' => 'The municipality fact-checked and accepted this incident.']);
            DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'hazard_report_id' => $report->id, 'action' => 'fact_checked', 'data' => json_encode(['method' => $data['method'], 'verification_note' => $data['verification_note']]), 'created_at' => now(), 'updated_at' => now()]);
            app(SecurityEvents::class)->record('report_fact_checked', $request->user(), $request);
            app(ReportUpdates::class)->notify($report, 'The municipality verified and accepted your report.');

            return $report;
        });

        return new HazardResource($report->load(['category', 'department', 'photos', 'history', 'reporter'])->loadCount('confirmations'));
    }
}
