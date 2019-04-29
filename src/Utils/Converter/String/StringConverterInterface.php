<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

interface StringConverterInterface
{
    public function convert(?string $tokenName): string;
}
