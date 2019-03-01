<?php

namespace App\Utils\Converter;

use App\Entity\Token\Token;

interface TokenNameConverterInterface
{
    public function convert(Token $token): string;
}
