<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dashboard and the activity-log listing both order the trail by
     * created_at on every load (Activity::latest() → ORDER BY created_at DESC),
     * but the Spatie-created table has no index on that column. As the log
     * grows this forces a full scan + sort. Add the missing b-tree index.
     *
     * Uses config('activitylog.table_name') to target the exact table the
     * package created, rather than hardcoding 'activity_log'.
     */
    public function up(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
