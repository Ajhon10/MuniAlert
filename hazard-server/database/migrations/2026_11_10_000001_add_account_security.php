<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('security_version')->default(0);
            $table->text('mfa_secret')->nullable();
            $table->timestamp('mfa_confirmed_at')->nullable();
            $table->timestamp('mfa_enrollment_until')->nullable();
            $table->unsignedBigInteger('mfa_last_step')->nullable();
            $table->unsignedTinyInteger('mfa_failures')->default(0);
            $table->timestamp('mfa_locked_until')->nullable();
            $table->text('mfa_recovery_codes')->nullable();
            $table->timestamp('local_email_verified_at')->nullable();
        });
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedInteger('security_version')->default(0);
            $table->timestamp('mfa_verified_at')->nullable();
            $table->timestamp('reauthenticated_at')->nullable();
        });
        Schema::create('security_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('purpose', 30);
            $table->unsignedInteger('security_version');
            $table->string('fingerprint', 64);
            $table->string('role', 20);
            $table->string('platform', 10);
            $table->string('binding', 64)->nullable();
            $table->text('payload')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('consumed')->default(false);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50)->index();
            $table->string('source_hash', 64)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('security_challenges');
        Schema::table('personal_access_tokens', fn (Blueprint $table) => $table->dropColumn(['security_version', 'mfa_verified_at', 'reauthenticated_at']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_active', 'security_version', 'mfa_secret', 'mfa_confirmed_at', 'mfa_enrollment_until', 'mfa_last_step', 'mfa_failures', 'mfa_locked_until', 'mfa_recovery_codes', 'local_email_verified_at']));
    }
};
