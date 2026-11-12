<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HazardReport;
use App\Services\SecurityEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ImageReviewController extends Controller
{
    public function store(Request $request, HazardReport $report, string $photo): array
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'assessment_version' => 'required|integer|min:0',
            'verdict' => ['required', Rule::in(['accepted_evidence', 'suspected_ai', 'inconclusive'])],
            'review_note' => 'required|string|min:15|max:1000', 'confirmed_reviewed' => 'required|accepted',
        ]);
        DB::transaction(function () use ($request, $report, $photo, $data) {
            $locked = HazardReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $image = $locked->photos()->whereKey($photo)->lockForUpdate()->firstOrFail();
            abort_if($image->assessment_version !== $data['assessment_version'], 409, 'The assessment changed. Refresh and review the latest result.');
            if ($image->ai_status === 'queued') {
                // Explicit human review cancels a pending result so it cannot
                // silently change the evidence after factual acceptance.
                $image->forceFill(['ai_status' => 'not_assessed', 'assessment_version' => $image->assessment_version + 1]);
            }
            $image->forceFill(['review_verdict' => $data['verdict'], 'review_note' => $data['review_note'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'reviewed_assessment_version' => $image->assessment_version])->save();
            DB::table('audit_logs')->insert(['actor_id' => $request->user()->id, 'hazard_report_id' => $locked->id, 'action' => 'image_reviewed', 'data' => json_encode(['photo_id' => $image->id, 'assessment_version' => $image->assessment_version, 'verdict' => $data['verdict']]), 'created_at' => now(), 'updated_at' => now()]);
            app(SecurityEvents::class)->record('report_image_reviewed', $request->user(), $request);
        });

        return ['reviewed' => true];
    }
}
