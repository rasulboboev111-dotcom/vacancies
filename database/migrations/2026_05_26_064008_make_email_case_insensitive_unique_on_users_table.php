<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First trim and lowercase existing user emails in the DB to avoid any violation errors during index creation
        DB::statement('UPDATE users SET email = LOWER(TRIM(email))');

        Schema::table('users', function (Blueprint $table) {
            // Drop old standard unique index on email
            $table->dropUnique('users_email_unique');
        });

        // Add case-insensitive unique index on LOWER(TRIM(email))
        DB::statement('CREATE UNIQUE INDEX users_email_case_insensitive_unique ON users (LOWER(TRIM(email))) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_case_insensitive_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email', 'users_email_unique');
        });
    }
};
