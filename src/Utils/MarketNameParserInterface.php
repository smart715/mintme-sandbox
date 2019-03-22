<?php declare(strict_types = 1);

namespace App\Utils;

interface MarketNameParserInterface
{
    public function parseSymbol(string $market): string;

    public function parseName(string $market): string;
}
