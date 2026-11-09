<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('citizen'));
        Schema::create('departments', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->string('department_id');
            $t->foreign('department_id')->references('id')->on('departments');
        });
        Schema::create('hazard_reports', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('reference_number')->unique();
            $t->foreignId('user_id')->constrained();
            $t->string('category_id');
            $t->foreign('category_id')->references('id')->on('categories');
            $t->string('title', 120);
            $t->text('description');
            $t->string('address', 200);
            $t->string('barangay', 80);
            $t->double('selected_latitude');
            $t->double('selected_longitude');
            $t->double('gps_latitude')->nullable();
            $t->double('gps_longitude')->nullable();
            $t->double('gps_accuracy')->nullable();
            $t->string('location_source');
            $t->double('location_difference_meters')->nullable();
            $t->double('verified_latitude')->nullable();
            $t->double('verified_longitude')->nullable();
            $t->string('severity')->default('moderate');
            $t->string('status')->default('submitted');
            $t->string('department_id');
            $t->foreign('department_id')->references('id')->on('departments');
            $t->string('assigned_responder')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamp('responded_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
            $t->index(['category_id', 'created_at']);
            $t->index(['selected_latitude', 'selected_longitude']);
        });
        Schema::create('hazard_photos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('hazard_report_id')->constrained()->cascadeOnDelete();
            $t->string('disk');
            $t->string('path');
            $t->string('original_name');
            $t->string('sha256', 64);
            $t->unsignedInteger('bytes');
            $t->timestamps();
        });
        Schema::create('report_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('hazard_report_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->constrained('users');
            $t->string('status');
            $t->text('note');
            $t->timestamps();
        });
        Schema::create('report_confirmations', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('hazard_report_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->timestamps();
            $t->unique(['hazard_report_id', 'user_id']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users');
            $t->foreignUuid('hazard_report_id')->constrained();
            $t->string('action');
            $t->json('data');
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('device_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('token');
            $t->string('token_hash', 64)->unique();
            $t->string('platform');
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->string('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['personal_access_tokens', 'device_tokens', 'notifications', 'audit_logs', 'report_confirmations', 'report_status_history', 'hazard_photos', 'hazard_reports', 'categories', 'departments'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
