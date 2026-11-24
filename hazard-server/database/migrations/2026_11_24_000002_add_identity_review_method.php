<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', fn (Blueprint $table) => $table->string('review_method', 32)->nullable());
    }

    public function down(): void
    {
        Schema::table('identity_verifications', fn (Blueprint $table) => $table->dropColumn('review_method'));
    }
};
