<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Reason for dismissal — nullable at the DB level
     * (active employees have none); it is required at validation time whenever
     * a dismissal_date is provided.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('dismissal_reason')->nullable()->after('dismissal_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('dismissal_reason');
        });
    }
};
