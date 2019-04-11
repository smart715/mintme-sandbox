<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;

interface TokenNameConverterInterface
{
    public function convert(Token $token): string;
    public static function parse(string $name): ?string;
    public static function dashedName(string $name): ?string;
}
