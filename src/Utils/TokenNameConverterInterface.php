<?php

namespace App\Utils;

use App\Entity\Token;

interface TokenNameConverterInterface
{
    public function convert(Token $token): string;
}