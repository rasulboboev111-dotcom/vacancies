<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class LookupResolver
{
    /**
     * Resolve a free-text value to the id of a lookup row, creating the row
     * when it does not yet exist. Empty input resolves to null. Matching is
     * case-insensitive and whitespace-insensitive to mirror the unique
     * indexes enforced at the database level.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function resolve(string $modelClass, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $model = $modelClass::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->first();

        if (! $model) {
            try {
                $model = $modelClass::create(['name' => $name]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request inserted the same value first — re-read it
                // (the case-insensitive unique index guarantees a single row).
                $model = $modelClass::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->firstOrFail();
            }
        }

        return $model->id;
    }
}
