<?php

namespace App\Enums;

/**
 * Статус жизненного цикла, общий для сущностей оргструктуры (филиал / отдел /
 * сотрудник), повторяющий значения «Active»/«Inactive» исходной системы. Импортёр
 * приводит неизвестные исходные значения к null (или к значению Active по
 * умолчанию для филиалов), так что в колонке всегда лежат валидные для enum строки.
 */
enum OrgStatus: string implements HasLabel
{
    use HasOptions;

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
