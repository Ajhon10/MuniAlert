<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 32);
            $table->timestamp('accepted_at');
            $table->unique(['user_id', 'version']);
        });
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('applicant_name', 120);
            $table->string('document_type', 32);
            $table->string('status', 20)->index();
            $table->string('path')->nullable();
            $table->string('consent_version', 32);
            $table->timestamp('consented_at');
            $table->timestamp('expires_at')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reason', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
        Schema::dropIfExists('policy_acceptances');
    }
};
