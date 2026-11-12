<?php

namespace App\Jobs;

use App\Mail\ReportStatusUpdate;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AccountMailDelivery;
use App\Services\AccountRecovery;
use App\Services\SecurityEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendReportEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 25;

    public int $backoff = 60;

    public function __construct(public string $notificationId) {}

    public function handle(AccountMailDelivery $delivery, AccountRecovery $recovery): void
    {
        $notification = DB::table('notifications')->where('id', $this->notificationId)->where('type', 'report_updated')->where('notifiable_type', User::class)->first();
        if (! $notification) {
            return;
        }
        $user = User::find($notification->notifiable_id);
        $data = json_decode($notification->data, true);
        $report = HazardReport::find($data['report_id'] ?? null);
        if (! $user || ! $report || $report->user_id !== $user->id || ! $user->is_active || ! $user->email_report_updates || ! $user->local_email_verified_at || ! $delivery->recipientAllowed($user->email)) {
            return;
        }
        if (! $recovery->available()) {
            throw new \RuntimeException('Account email delivery is unavailable; the job may retry.');
        }
        try {
            Mail::to($user->email)->send(new ReportStatusUpdate($report->reference_number, ucfirst(str_replace('_', ' ', $data['status'] ?? $report->status)), rtrim(config('security.frontend_url'), '/').'/?view=mine'));
        } catch (\Throwable) {
            app(SecurityEvents::class)->record('report_mail_failed', $user);
            throw new \RuntimeException('Report email delivery failed; the job may retry.');
        }
    }
}
