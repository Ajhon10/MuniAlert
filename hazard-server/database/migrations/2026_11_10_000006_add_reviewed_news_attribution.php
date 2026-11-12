<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->string('public_reporter_name')->nullable();
            $table->timestamp('public_occurred_at')->nullable();
        });
        // Existing publications retain their previous anonymous presentation.
        // Attribution and incident time require an explicit reviewed publication.
    }

    public function down(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->dropColumn(['public_reporter_name', 'public_occurred_at']);
        });
    }
};
