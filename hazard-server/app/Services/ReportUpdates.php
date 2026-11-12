<?php

namespace App\Services;

use App\Jobs\SendReportEmail;
use App\Jobs\SendReportPush;
use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportUpdates
{
    /** Called inside the report transaction; only reporter-safe text belongs here. */
    public function notify(HazardReport $report, string $message): void
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $id, 'type' => 'report_updated', 'notifiable_type' => User::class, 'notifiable_id' => $report->user_id,
            'data' => json_encode(['report_id' => $report->id, 'reference_number' => $report->reference_number, 'message' => $message, 'status' => $report->status]), 'created_at' => now(), 'updated_at' => now()]);
        $recipient = User::find($report->user_id);
        if ($recipient && $recipient->email_report_updates && $recipient->local_email_verified_at && app(AccountRecovery::class)->available() && app(AccountMailDelivery::class)->recipientAllowed($recipient->email)) {
            SendReportEmail::dispatch($id)->onConnection('security-mail')->onQueue('security-mail')->beforeCommit();
        }
        if (config('hazards.fcm_project_id') && config('hazards.fcm_credentials')) {
            foreach (DB::table('device_tokens')->where('user_id', $report->user_id)->select('id')->cursor() as $device) {
                SendReportPush::dispatch($report->user_id, $report->id, $report->reference_number, ucfirst(str_replace('_', ' ', $report->status)), $device->id, $id)
                    ->onConnection('report-push')->beforeCommit();
            }
        }
    }
}
