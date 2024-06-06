<?php declare(strict_types = 1);

namespace App\Utils\Converter;

interface NetworkSymbolConverterInterface
{
    public function convert(string $name): ?string;
}
