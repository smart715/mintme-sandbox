<?php declare(strict_types = 1);

namespace App\Utils\Converter;

interface TokenNameNormalizerInterface
{
    public function parse(?string $TokenName): string;
    public function dashed(?string $TokenName): string;
}
