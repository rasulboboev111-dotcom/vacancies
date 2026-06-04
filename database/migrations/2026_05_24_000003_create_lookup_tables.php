<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup vocabularies referenced by employees/vacancies. The
     * free-text-derived lookups and positions enforce a case-insensitive
     * (LOWER(TRIM(name))) unique to mirror the resolver. (Employee category is
     * a fixed enum, not a lookup table — see App\Enums\Category.)
     */
    public function up(): void
    {
        foreach (['nationalities', 'educations', 'specialties', 'birth_places'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
            DB::statement("CREATE UNIQUE INDEX {$lookup}_name_lower_unique ON {$lookup} (LOWER(TRIM(name)))");
        }

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('external_id')->nullable()->unique();
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX positions_name_lower_unique ON positions (LOWER(TRIM(name)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['positions', 'birth_places', 'specialties', 'educations', 'nationalities'] as $lookup) {
            Schema::dropIfExists($lookup);
        }
    }
};
