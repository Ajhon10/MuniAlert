<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_photos', function (Blueprint $table) {
            $table->boolean('authenticity_review_required')->default(false);
            $table->string('ai_status', 24)->default('not_assessed');
            $table->decimal('ai_probability', 6, 5)->nullable();
            $table->string('ai_model', 160)->nullable();
            $table->timestamp('ai_assessed_at')->nullable();
            $table->unsignedInteger('assessment_version')->default(0);
            $table->string('review_verdict', 24)->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('reviewed_assessment_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hazard_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['authenticity_review_required', 'ai_status', 'ai_probability', 'ai_model', 'ai_assessed_at', 'assessment_version', 'review_verdict', 'review_note', 'reviewed_at', 'reviewed_assessment_version']);
        });
    }
};
