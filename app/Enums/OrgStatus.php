<?php

namespace App\Enums;

/**
 * Lifecycle status shared by org-structure entities (branch / department /
 * employee), mirroring the source system's "Active"/"Inactive" values. The
 * importer coerces unknown source values to null (or the Active default for
 * branches) so the column only ever holds enum-valid strings.
 */
enum OrgStatus: string
{
    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Фаъол',
            self::INACTIVE => 'Ғайрифаъол',
        };
    }
}
