<?php

namespace App\Services;

use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminReportAlerts
{
    /** Insert inside the report transaction; no uncommitted alert can escape. */
    public function submitted(HazardReport $report): void
    {
        foreach (User::where('role', 'admin')->where('is_active', true)->select('id')->cursor() as $admin) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(), 'type' => 'report_submitted', 'notifiable_type' => User::class, 'notifiable_id' => $admin->id,
                'data' => json_encode(['report_id' => $report->id, 'reference_number' => $report->reference_number,
                    'category_id' => $report->category_id, 'assistance_needed' => (bool) $report->assistance_needed,
                    'message' => $report->assistance_needed ? 'New report: immediate assistance requested. Review and contact the appropriate agency.' : 'New hazard submitted for municipal review.']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
