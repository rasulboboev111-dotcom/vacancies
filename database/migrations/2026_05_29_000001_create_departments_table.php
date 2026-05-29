<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('departments')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('code', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'parent_id']);
        });

        DB::statement('
            CREATE UNIQUE INDEX departments_branch_root_name_unique
            ON departments (branch_id, name)
            WHERE parent_id IS NULL AND deleted_at IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX departments_branch_parent_name_unique
            ON departments (branch_id, parent_id, name)
            WHERE parent_id IS NOT NULL AND deleted_at IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
