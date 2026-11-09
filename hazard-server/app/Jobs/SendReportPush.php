<?php

namespace App\Jobs;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SendReportPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 25;

    // Defaults preserve deserialization of jobs queued before per-device delivery.
    public ?int $deviceId = null;

    public ?string $notificationId = null;

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public int $userId, public string $reportId, public string $reference, public string $status, ?int $deviceId = null, ?string $notificationId = null)
    {
        $this->deviceId = $deviceId;
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        $project = config('hazards.fcm_project_id');
        $file = config('hazards.fcm_credentials');
        if (! $project || ! $file) {
            return;
        }
        if ($this->deviceId === null) {
            // Upgrade a legacy batch job into isolated jobs atomically. A failure
            // on one device no longer repeats delivery to every successful device.
            DB::transaction(function () {
                foreach (DB::table('device_tokens')->where('user_id', $this->userId)->select('id')->cursor() as $device) {
                    self::dispatch($this->userId, $this->reportId, $this->reference, $this->status, $device->id, $this->notificationId)
                        ->onConnection('report-push')->beforeCommit();
                }
            });

            return;
        }
        $device = DB::table('device_tokens')->where('id', $this->deviceId)->where('user_id', $this->userId)->first();
        if (! $device) {
            return;
        }
        $access = Cache::remember('fcm-access-'.hash('sha256', $project), 3000, function () use ($file) {
            $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $file);

            // Shared hosting may not have pcntl, so bound OAuth network time
            // independently of the queue worker's optional signal timeout.
            $http = HttpHandlerFactory::build(new Client(['connect_timeout' => 5, 'timeout' => 8]));

            return $credentials->fetchAuthToken($http)['access_token'];
        });
        $response = Http::withToken($access)->connectTimeout(5)->timeout(15)->post("https://fcm.googleapis.com/v1/projects/$project/messages:send", ['message' => ['token' => $device->token, 'notification' => ['title' => 'Report update · '.$this->reference, 'body' => $this->status], 'data' => ['report_id' => $this->reportId, 'notification_id' => $this->notificationId ?? $this->reportId]]]);
        $unregistered = collect($response->json('error.details', []))->contains(fn ($detail) => ($detail['errorCode'] ?? null) === 'UNREGISTERED');
        if ($unregistered) {
            DB::table('device_tokens')->where('id', $device->id)->delete();
        } else {
            $response->throw();
        }
    }
}
