<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HazardReport extends Model
{
    public const ACCEPTED_STATUSES = ['verified', 'assigned', 'responding', 'resolved', 'closed'];

    use HasUuids, SoftDeletes;

    protected $attributes = ['status' => 'submitted', 'severity' => 'moderate'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['assistance_needed' => 'boolean', 'selected_latitude' => 'float', 'selected_longitude' => 'float', 'gps_latitude' => 'float', 'gps_longitude' => 'float', 'gps_accuracy' => 'float', 'verified_latitude' => 'float', 'verified_longitude' => 'float', 'location_difference_meters' => 'float', 'verified_at' => 'datetime', 'responded_at' => 'datetime', 'resolved_at' => 'datetime', 'occurred_at' => 'datetime', 'people_affected' => 'integer', 'fact_checked_at' => 'datetime', 'published_at' => 'datetime', 'public_occurred_at' => 'datetime'];
    }

    /** @param Builder<static> $query @return Builder<static> */
    public function scopeCommunityVisible(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACCEPTED_STATUSES)->whereNotNull('fact_checked_at');
    }

    /** @param Builder<static> $query @return Builder<static> */
    public function scopePublished(Builder $query): Builder
    {
        return $query->communityVisible()->whereNotNull('published_at')->whereNotNull('public_title')->where('public_title', '<>', '')->whereNotNull('public_summary')->where('public_summary', '<>', '');
    }

    public function isCommunityVisible(): bool
    {
        return ! $this->trashed() && $this->fact_checked_at !== null && in_array($this->status, self::ACCEPTED_STATUSES, true);
    }

    public function isPublished(): bool
    {
        return $this->isCommunityVisible() && $this->published_at !== null && filled($this->public_title) && filled($this->public_summary);
    }

    /** @return HasMany<HazardPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(HazardPhoto::class);
    }

    /** @return HasMany<StatusHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->oldest('id');
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<ReportConfirmation, $this> */
    public function confirmations(): HasMany
    {
        return $this->hasMany(ReportConfirmation::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
