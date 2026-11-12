<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->timestamp('fact_checked_at')->nullable()->index();
            $table->foreignId('fact_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fact_check_method', 40)->nullable();
            $table->text('fact_check_note')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
        });
        // Legacy statuses/public text are retained privately, never certified or
        // published automatically. An administrator must explicitly review them.
    }

    public function down(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fact_checked_by');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['fact_checked_at', 'fact_check_method', 'fact_check_note', 'published_at']);
        });
    }
};
