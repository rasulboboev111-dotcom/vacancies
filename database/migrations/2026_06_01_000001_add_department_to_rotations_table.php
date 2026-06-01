<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rotations', function (Blueprint $table) {
            $table->foreignId('old_department_id')->nullable()->after('new_structure_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('new_department_id')->nullable()->after('old_department_id')->constrained('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('old_department_id');
            $table->dropConstrainedForeignId('new_department_id');
        });
    }
};
