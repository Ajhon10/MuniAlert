<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_reports', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('municipal_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('threshold')->unique();
            $table->string('name', 80);
            $table->string('color', 20);
            $table->text('description');
            $table->string('reward_type', 20)->default('none');
            $table->decimal('cash_amount', 12, 2)->nullable();
            $table->string('reward_description', 255)->nullable();
            $table->timestamps();
        });
        Schema::create('news_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hazard_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->timestamps();
            $table->unique(['hazard_report_id', 'user_id']);
        });
        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hazard_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        DB::table('municipal_settings')->insert([
            ['key' => 'map', 'value' => json_encode(['latitude' => 14.5995, 'longitude' => 120.9842, 'zoom' => 13]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'hotlines', 'value' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach ([[50, 'Bronze', 'bronze'], [100, 'Silver', 'silver'], [1000, 'Gold', 'gold'], [10000, 'Platinum', 'platinum']] as [$threshold, $name, $color]) {
            DB::table('badges')->insert(['threshold' => $threshold, 'name' => $name, 'color' => $color, 'description' => "Earned after {$threshold} verified hazard reports.", 'reward_type' => 'none', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news_comments');
        Schema::dropIfExists('news_reactions');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('municipal_settings');
        Schema::table('hazard_reports', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
