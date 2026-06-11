<?php

namespace App\Enums;

/**
 * Backed enum, кейсы которого несут читаемую человеком метку — точную
 * формулировку, печатаемую в форме «Заявка на подбор персонала».
 */
interface HasLabel
{
    public function label(): string;
}
