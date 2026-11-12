<?php

namespace App\Http\Resources;

use App\Models\HazardPhoto;
use App\Models\HazardReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HazardReport */
class HazardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Public feeds never acquire private fields just because an admin is signed in.
        $private = $request->routeIs('reports.mine', 'reports.admin', 'reports.show', 'reports.store', 'reports.update', 'reports.verify', 'reports.publish', 'reports.unpublish')
            && $request->user() && ($request->user()->role === 'admin' || $request->user()->id === $this->user_id);

        return [
            'id' => $this->id, 'reference_number' => $this->reference_number,
            'title' => $private ? $this->title : ($this->isPublished() ? $this->public_title : $this->category->name.' report'),
            'description' => $private ? $this->description : ($this->isPublished() ? $this->public_summary : 'This hazard was verified by the municipality. A community news post has not been published.'),
            'address' => $private ? $this->address : 'Approximate area only', 'barangay' => $this->barangay, 'category' => $this->category, 'department' => $this->department,
            'selected_latitude' => $private ? $this->selected_latitude : round($this->selected_latitude, 2),
            'selected_longitude' => $private ? $this->selected_longitude : round($this->selected_longitude, 2),
            'verified_latitude' => $this->verified_latitude === null ? null : ($private ? $this->verified_latitude : round($this->verified_latitude, 2)),
            'verified_longitude' => $this->verified_longitude === null ? null : ($private ? $this->verified_longitude : round($this->verified_longitude, 2)),
            'approximate_location' => ! $private,
            'fact_checked_at' => $this->fact_checked_at, 'published_at' => $this->published_at,
            'public_reporter_name' => ($private || $this->isPublished()) ? $this->public_reporter_name : null,
            'public_occurred_at' => ($private || $this->isPublished()) ? $this->public_occurred_at : null,
            'status' => $this->status, 'severity' => $this->severity, 'confirmations_count' => $this->confirmations_count ?? $this->confirmations()->count(),
            'created_at' => $this->created_at, 'updated_at' => $this->updated_at, 'verified_at' => $this->verified_at, 'responded_at' => $this->responded_at, 'resolved_at' => $this->resolved_at,
            'photos' => $private ? $this->photos->map(fn ($photo) => $this->photo($photo, $request)) : [],
            $this->mergeWhen($private, fn () => [
                'user_id' => $this->user_id, 'reporter' => ['name' => $this->reporter->name, 'email' => $this->reporter->email],
                'assistance_needed' => (bool) $this->assistance_needed, 'occurred_at' => $this->occurred_at, 'injuries' => $this->injuries, 'people_affected' => $this->people_affected,
                'public_title' => $this->public_title, 'public_summary' => $this->public_summary,
                ...($request->user()->role === 'admin' ? ['fact_check_method' => $this->fact_check_method, 'fact_check_note' => $this->fact_check_note, 'fact_checked_by' => $this->fact_checked_by] : []),
                'gps_latitude' => $this->gps_latitude, 'gps_longitude' => $this->gps_longitude, 'gps_accuracy' => $this->gps_accuracy,
                'location_source' => $this->location_source, 'location_difference_meters' => $this->location_difference_meters,
                'location_review_required' => $this->location_difference_meters > config('hazards.location_review_meters') || ($this->gps_accuracy !== null && $this->gps_accuracy > 100),
                'assigned_responder' => $this->assigned_responder,
                'history' => $this->history->map(fn ($history) => ['id' => $history->id, 'status' => $history->status, 'note' => $history->note, 'created_at' => $history->created_at]),
            ]),
        ];
    }

    private function photo(HazardPhoto $photo, Request $request): array
    {
        return ['id' => $photo->id, 'url' => '/api/reports/'.$this->id.'/photos/'.$photo->id, 'photo_type' => $photo->photo_type,
            ...($request->user()->role === 'admin' ? ['authenticity' => [
                'required' => (bool) $photo->authenticity_review_required, 'status' => $photo->ai_status,
                'probability' => $photo->ai_probability, 'model' => $photo->ai_model, 'version' => $photo->assessment_version,
                'review' => $photo->reviewed_at ? ['verdict' => $photo->review_verdict, 'note' => $photo->review_note, 'at' => $photo->reviewed_at, 'version' => $photo->reviewed_assessment_version] : null,
            ]] : []),
        ];
    }
}
