<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire the legacy `structures` model in favour of `departments`, which is
     * the canonical organisational-unit hierarchy. The redundant structure_id
     * columns are removed and the structures table dropped. Any employee that
     * still has a structure but no department is migrated first so the org-unit
     * assignment is preserved (a root department is created per branch from the
     * structure name).
     */
    public function up(): void
    {
        // 1. Backfill department_id from the legacy structure where missing.
        $employees = DB::table('employees')
            ->whereNotNull('structure_id')
            ->whereNull('department_id')
            ->get(['id', 'branch_id', 'structure_id']);

        if ($employees->isNotEmpty()) {
            $structureNames = DB::table('structures')->pluck('name', 'id');
            $now = now();

            foreach ($employees as $employee) {
                $name = $structureNames[$employee->structure_id] ?? null;

                if ($name === null || $employee->branch_id === null) {
                    continue;
                }

                $departmentId = DB::table('departments')
                    ->where('branch_id', $employee->branch_id)
                    ->where('name', $name)
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at')
                    ->value('id');

                if ($departmentId === null) {
                    $departmentId = DB::table('departments')->insertGetId([
                        'branch_id' => $employee->branch_id,
                        'parent_id' => null,
                        'name' => $name,
                        'code' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['department_id' => $departmentId]);
            }
        }

        // 2. Drop the structure foreign keys / columns.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('structure_id');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('structure_id');
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('old_structure_id');
            $table->dropConstrainedForeignId('new_structure_id');
        });

        // 3. Drop the now-unused structures table.
        Schema::dropIfExists('structures');
    }

    /**
     * Reverse the migrations (schema only — migrated data is not restored).
     */
    public function down(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('structure_id')->nullable()->after('position_id')->constrained('structures')->nullOnDelete();
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->foreignId('structure_id')->nullable()->constrained('structures')->nullOnDelete();
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->foreignId('old_structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->foreignId('new_structure_id')->nullable()->constrained('structures')->nullOnDelete();
        });
    }
};
