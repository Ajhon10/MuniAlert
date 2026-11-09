<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = json_decode(DB::table('municipal_settings')->where('key', 'map')->value('value') ?? '{}', true);
        if (isset($map['latitude'], $map['longitude']) && abs($map['latitude'] - 14.5995) < 0.000001 && abs($map['longitude'] - 120.9842) < 0.000001) {
            DB::table('municipal_settings')->where('key', 'map')->update(['value' => json_encode(['latitude' => 15.865448, 'longitude' => 120.400132, 'zoom' => 12.5]), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Camera preferences are not reverted over a municipal administrator's edits.
    }
};
