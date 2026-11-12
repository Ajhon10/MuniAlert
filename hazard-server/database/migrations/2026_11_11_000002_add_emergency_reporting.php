<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_reports', function (Blueprint $table) {
            $table->boolean('assistance_needed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('hazard_reports', fn (Blueprint $table) => $table->dropColumn('assistance_needed'));
    }
};
