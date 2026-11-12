<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('municipal_settings')->insertOrIgnore(['key' => 'dashboard_background', 'value' => 'null', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('municipal_settings')->where('key', 'dashboard_background')->delete();
    }
};
