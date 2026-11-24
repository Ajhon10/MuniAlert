<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('admin_account_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users');
            $table->foreignId('target_id')->constrained('users');
            $table->string('action', 40);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_account_actions');
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
