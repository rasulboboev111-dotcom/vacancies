<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-text employee columns that are extracted into their own lookup tables.
     *
     * @var array<string, array{table: string, column: string}>
     */
    private array $lookups = [
        'nationality' => ['table' => 'nationalities', 'column' => 'nationality_id'],
        'education'   => ['table' => 'educations',    'column' => 'education_id'],
        'specialty'   => ['table' => 'specialties',   'column' => 'specialty_id'],
        'birth_place' => ['table' => 'birth_places',  'column' => 'birth_place_id'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the lookup tables.
        foreach ($this->lookups as $config) {
            Schema::create($config['table'], function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // 2. Backfill each lookup table from the distinct free-text values
        //    currently stored on the employees table.
        $now = now();
        foreach ($this->lookups as $source => $config) {
            $names = DB::table('employees')
                ->whereNotNull($source)
                ->where($source, '!=', '')
                ->distinct()
                ->pluck($source);

            $rows = $names
                ->map(fn ($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
                ->all();

            if ($rows) {
                DB::table($config['table'])->insertOrIgnore($rows);
            }
        }

        // 3. Add the nullable FK columns referencing the new lookup tables.
        Schema::table('employees', function (Blueprint $table) {
            foreach ($this->lookups as $source => $config) {
                $table->foreignId($config['column'])
                    ->nullable()
                    ->after($source)
                    ->constrained($config['table'])
                    ->nullOnDelete();
            }
        });

        // 4. Backfill the FK columns by matching the old string value to the
        //    lookup table name.
        foreach ($this->lookups as $source => $config) {
            DB::statement("
                UPDATE employees AS e
                SET {$config['column']} = l.id
                FROM {$config['table']} AS l
                WHERE e.{$source} = l.name
            ");
        }

        // 5. Drop the now-redundant free-text columns.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(array_keys($this->lookups));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-create the free-text columns.
        Schema::table('employees', function (Blueprint $table) {
            foreach ($this->lookups as $source => $config) {
                $table->string($source)->nullable()->after($config['column']);
            }
        });

        // 2. Restore the string values from the lookup tables.
        foreach ($this->lookups as $source => $config) {
            DB::statement("
                UPDATE employees AS e
                SET {$source} = l.name
                FROM {$config['table']} AS l
                WHERE e.{$config['column']} = l.id
            ");
        }

        // 3. Drop the FK columns.
        Schema::table('employees', function (Blueprint $table) {
            foreach ($this->lookups as $config) {
                $table->dropConstrainedForeignId($config['column']);
            }
        });

        // 4. Drop the lookup tables.
        foreach ($this->lookups as $config) {
            Schema::dropIfExists($config['table']);
        }
    }
};
