<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('password_enabled')->default(true));
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16);
            $table->string('provider_id', 255);
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
        Schema::create('social_auth_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 16);
            $table->string('platform', 10);
            $table->string('mode', 10);
            $table->string('return_origin')->nullable();
            $table->string('intent', 20)->default('overview');
            $table->string('code_challenge', 43)->nullable();
            $table->foreignId('link_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auth_fingerprint', 64)->nullable();
            $table->unsignedBigInteger('access_token_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exchange_code_hash', 64)->nullable();
            $table->unsignedTinyInteger('failures')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_auth_attempts');
        Schema::dropIfExists('social_accounts');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('password_enabled'));
    }
};
