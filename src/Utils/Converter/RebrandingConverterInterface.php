<?php declare(strict_types = 1);

namespace App\Utils\Converter;

interface RebrandingConverterInterface
{
    public function convert(string $value): string;
    public function reverseConvert(string $value): string;
}
