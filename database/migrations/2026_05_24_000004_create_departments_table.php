<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. manager_id references employees, which does not exist
     * yet, so its foreign key is added later (add_department_manager_foreign_key).
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('status')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'parent_id']);
        });

        DB::statement('CREATE UNIQUE INDEX departments_branch_root_name_unique ON departments (branch_id, name) WHERE parent_id IS NULL AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX departments_branch_parent_name_unique ON departments (branch_id, parent_id, name) WHERE parent_id IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX departments_external_id_unique ON departments (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL');

        // Status enum enforced at the DB level (App\Enums\OrgStatus); NULL passes.
        DB::statement("ALTER TABLE departments ADD CONSTRAINT departments_status_check CHECK (status IN ('Active', 'Inactive'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
