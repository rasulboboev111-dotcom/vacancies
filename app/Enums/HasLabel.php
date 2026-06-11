<?php

namespace App\Enums;

/**
 * A backed enum whose cases carry a human-readable label — the exact wording
 * printed on the «Заявка на подбор персонала» form.
 */
interface HasLabel
{
    public function label(): string;
}
