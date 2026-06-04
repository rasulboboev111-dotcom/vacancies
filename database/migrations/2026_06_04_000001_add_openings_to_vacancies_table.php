<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Number of people needed for the vacancy. Existing rows backfill to 1
     * (a vacancy is for at least one opening).
     */
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->unsignedInteger('openings')->default(1)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn('openings');
        });
    }
};
