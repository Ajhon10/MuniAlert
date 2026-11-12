<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityEvents
{
    public function record(string $event, ?User $user = null, ?Request $request = null): void
    {
        // No arbitrary context: never collect credentials, email, GPS or payloads.
        DB::table('security_events')->insert([
            'user_id' => $user?->id,
            'event' => $event,
            'source_hash' => $request ? hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')) : null,
            'created_at' => now(),
        ]);
    }
}
