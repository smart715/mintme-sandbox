<?php declare(strict_types = 1);

namespace App\Utils\Converter;

interface FriendlyUrlConverterInterface
{
    public function convert(string $name): ?string;
}
