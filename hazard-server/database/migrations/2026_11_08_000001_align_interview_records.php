<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->timestamp('occurred_at')->nullable()->index();
            $table->string('injuries', 10)->default('unknown');
            $table->unsignedInteger('people_affected')->nullable();
            $table->string('public_title', 120)->nullable();
            $table->text('public_summary')->nullable();
        });
        Schema::table('hazard_photos', fn (Blueprint $table) => $table->string('photo_type', 20)->default('incident'));
    }

    public function down(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
            $table->dropColumn(['occurred_at', 'injuries', 'people_affected', 'public_title', 'public_summary']);
        });
        Schema::table('hazard_photos', fn (Blueprint $table) => $table->dropColumn('photo_type'));
    }
};
