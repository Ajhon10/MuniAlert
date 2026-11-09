<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HazardReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $attributes = ['status' => 'submitted', 'severity' => 'moderate'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['selected_latitude' => 'float', 'selected_longitude' => 'float', 'gps_latitude' => 'float', 'gps_longitude' => 'float', 'gps_accuracy' => 'float', 'verified_latitude' => 'float', 'verified_longitude' => 'float', 'location_difference_meters' => 'float', 'verified_at' => 'datetime', 'responded_at' => 'datetime', 'resolved_at' => 'datetime', 'occurred_at' => 'datetime', 'people_affected' => 'integer'];
    }

    public function photos()
    {
        return $this->hasMany(HazardPhoto::class);
    }

    public function history()
    {
        return $this->hasMany(StatusHistory::class)->oldest('id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmations()
    {
        return $this->hasMany(ReportConfirmation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
