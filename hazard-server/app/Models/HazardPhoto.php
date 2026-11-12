<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HazardPhoto extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['authenticity_review_required' => 'boolean', 'ai_probability' => 'float', 'ai_assessed_at' => 'datetime', 'assessment_version' => 'integer', 'reviewed_at' => 'datetime', 'reviewed_assessment_version' => 'integer'];
    }
}
