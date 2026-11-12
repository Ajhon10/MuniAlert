<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (config('hazards.persistent_login')) {
            // Existing expired/revoked credentials are never revived.
            DB::table('personal_access_tokens')->where('name', 'mobile')->where('expires_at', '>', now())->update(['expires_at' => null]);
        }
    }

    public function down(): void
    {
        // Original per-device expiration dates cannot be reconstructed. Rolling
        // back application code must not silently revoke active credentials.
    }
};
