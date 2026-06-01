<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Branches are soft-deleted by the application, so a hard delete should
     * never silently cascade and wipe out an entire branch's employees,
     * departments and vacancies. Switch these foreign keys from ON DELETE
     * CASCADE to ON DELETE RESTRICT so an accidental hard delete is blocked
     * while dependent rows still exist.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });
    }
};
